<?php

namespace App\Jobs;

use App\Models\Sms;
use App\Models\Customer;
use App\Enums\SmsStatus;
use App\Models\Treatment;
use App\Models\Appointment;
use App\Models\SmsTemplate;
use Overtrue\EasySms\EasySms;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Sms $sms;

    /**
     * @var array|string[]
     */
    protected array $scenarioMaps = [
        'appointment' => Appointment::class,
        'treatment'   => Treatment::class,
        'customer'    => Customer::class,
    ];

    /**
     * Create a new job instance.
     *
     * @param Sms $sms
     */
    public function __construct(Sms $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $template = SmsTemplate::query()->find($this->sms->template_id);
        if (!$template) {
            $this->fail('短信模板不存在');
            return;
        }

        $easySms = new EasySms($this->getConfig());

        try {
            $variables          = $this->getVariables($template->content);
            $this->sms->content = str_replace(
                array_map(fn($k) => "\${{$k}}", array_keys($variables)),
                array_values($variables),
                $template->content
            );

            $result = $easySms->send($this->sms->phone, [
                'template' => $template->code,
                'data'     => $variables,
            ]);

            $this->sms->status           = SmsStatus::SENT;
            $this->sms->sent_at          = now();
            $this->sms->gateway_response = json_encode($result);
        } catch (NoGatewayAvailableException $e) {
            $this->sms->status = SmsStatus::FAILED;
            $details           = [];
            foreach ($e->getExceptions() as $gateway => $ex) {
                $details[] = $gateway . ': ' . $ex->getMessage();
            }
            $this->sms->error_message = '所有网关发送失败: ' . implode('; ', $details);
        } catch (InvalidArgumentException $exception) {
            $this->sms->status        = SmsStatus::FAILED;
            $this->sms->error_message = '参数错误: ' . $exception->getMessage();
        } finally {
            $this->sms->save();
        }
    }

    /**
     * 根据模板内容获取变量
     * @param string $templateContent
     * @return array
     */
    private function getVariables(string $templateContent): array
    {
        if (!preg_match_all('/\${(\w+)}/', $templateContent, $matches)) {
            return [];
        }

        $placeholders = $matches[1]; // like ['customer_name', 'appointment_date']

        $scenarioModelClass = $this->scenarioMaps[$this->sms->scenario] ?? null;
        if (!$scenarioModelClass || !$this->sms->scenario_id) {
            return [];
        }

        $businessData = $scenarioModelClass::query()->find($this->sms->scenario_id);
        if (!$businessData) {
            return [];
        }

        $customer = null;
        if ($this->sms->scenario === 'customer') {
            $customer = $businessData;
        } elseif (method_exists($businessData, 'customer')) {
            $customer = $businessData->customer;
        }

        $variableMapping = [
            'appointment' => [
                'appointment_date'  => 'date',
                'appointment_start' => 'start_time',
                'appointment_end'   => 'end_time',
            ],
            'treatment'   => [
                'treatment_date' => 'date',
                'treatment_time' => 'time',
            ],
        ];

        $vars = [];
        foreach ($placeholders as $placeholder) {
            // Common variables from customer
            if ($customer) {
                if ($placeholder === 'customer_name') {
                    $vars['customer_name'] = $customer->name;
                    continue;
                }
                if ($placeholder === 'customer_birthday') {
                    $vars['customer_birthday'] = $customer->birthday;
                    continue;
                }
            }

            // Scenario specific variables
            $fieldMap      = $variableMapping[$this->sms->scenario] ?? [];
            $attributeName = $fieldMap[$placeholder] ?? null;
            if ($attributeName && isset($businessData->{$attributeName})) {
                $vars[$placeholder] = $businessData->{$attributeName};
            }
        }

        return $vars;
    }


    /**
     * 任务失败
     * @param string|null $exception
     * @return void
     */
    public function fail(string $exception = null): void
    {
        $this->sms->status        = SmsStatus::FAILED;
        $this->sms->error_message = $exception;
        $this->sms->save();
    }

    /**
     * 短信配置
     * @return array
     */
    private function getConfig(): array
    {
        return [
            // HTTP 请求的超时时间（秒）
            'timeout'  => 5.0,

            // 默认发送配置
            'default'  => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                // 默认可用的发送网关
                'gateways' => [
                    'aliyun',
                ],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => '/tmp/easy-sms.log',
                ],
                'aliyun'   => [
                    'access_key_id'     => parameter('cywebos_sms_aliyun_access_key_id'),
                    'access_key_secret' => parameter('cywebos_sms_aliyun_access_key_secret'),
                    'sign_name'         => parameter('cywebos_sms_aliyun_sign_name'),
                ],
            ],
        ];
    }
}
