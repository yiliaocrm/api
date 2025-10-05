<?php
/**
 * 配置文件
 */
return [
    'bed'                      => [
        'status' => [
            0 => '停用',
            1 => '空闲',
            2 => '使用中',
            3 => '预约'
        ]
    ],
    'room'                     => [
        'status' => [
            0 => '停用',
            1 => '空闲',
            2 => '使用中',
            3 => '预约'
        ]
    ],
    'coupon'                   => [
        'status' => [
            1 => '上线',
            2 => '下线',
            3 => '过期'
        ]
    ],
    'coupon_details'           => [
        'status' => [
            1 => '未使用',
            2 => '部分使用',
            3 => '已使用',
            4 => '已过期',
            5 => '已作废',
        ]
    ],
    'cashier'                  => [
        'status'           => [
            1 => '未收费',
            2 => '已收费',
            3 => '已关闭'
        ],
        'cashierable_type' => [
            'App\Models\Recharge'         => '收费充值',
            'App\Models\Consultant'       => '现场咨询',
            'App\Models\Outpatient'       => '医生门诊',
            'App\Models\CashierRefund'    => '退款收费',
            'App\Models\CashierArrearage' => '还款收费',
            'App\Models\CashierRetail'    => '零售收费',
            'App\Models\Erkai'            => '二开零购',
            'App\Models\CouponDetail'     => '购卡换券',
        ]
    ],
    'cashier_arrearage'        => [
        'status' => [
            1 => '还款中',
            2 => '清讫',
            3 => '免单'
        ]
    ],
    'cashier_retail'           => [
        'status' => [
            1 => '挂单',
            2 => '成交'
        ]
    ],
    'cashier_refund'           => [
        'status' => [
            1 => '待审核',
            2 => '待收费',
            3 => '已收费',
            4 => '退单',
        ]
    ],
    'customer'                 => [
        'sex'     => [
            1 => '男',
            2 => '女'
        ],
        'marital' => [
            1 => '未知',
            2 => '未婚',
            3 => '已婚',
        ]
    ],
    'customer_log'             => [
        'action' => [
            'App\Http\Controllers\Web\MiniappController@change'                        => '修改小程序绑定',
            'App\Http\Controllers\Web\CustomerPhotoController@create'                  => '创建相册',
            'App\Http\Controllers\Web\CustomerPhotoController@update'                  => '更新相册',
            'App\Http\Controllers\Web\CustomerPhotoDetailController@download'          => '下载照片',
            'App\Http\Controllers\Web\CustomerPhotoDetailController@rename'            => '照片重命名',
            'App\Http\Controllers\Api\ReservationController@create'                => '创建网电咨询(APP)',
            'App\Http\Controllers\Web\TreatmentController@create'                      => '治疗划扣',
            'App\Http\Controllers\Web\CustomerController@create'                       => '创建顾客',
            'App\Http\Controllers\Web\CustomerController@update'                       => '更新客户资料',
            'App\Http\Controllers\Web\CustomerController@merge'                        => '合并档案',
            'App\Http\Controllers\Web\ReceptionController@create'                      => '分诊登记',
            'App\Http\Controllers\Web\IntegralController@adjust'                       => '调整积分',
            'App\Http\Controllers\Web\ReceptionController@update'                      => '修改分诊',
            'App\Http\Controllers\Web\ReceptionController@remove'                      => '删除分诊',
            'App\Http\Controllers\Web\ReceptionController@dispatchConsultant'          => '改派咨询',
            'App\Http\Controllers\Web\ReceptionController@dispatchDoctor'              => '改派医生',
            'App\Http\Controllers\Web\ReservationController@create'                    => '创建网电咨询',
            'App\Http\Controllers\Web\ReservationController@update'                    => '修改网电咨询',
            'App\Http\Controllers\Web\ReservationController@remove'                    => '删除网电咨询',
            'App\Http\Controllers\Web\FollowupController@save'                         => '保存回访',
            'App\Http\Controllers\Web\FollowupController@remove'                       => '删除回访',
            'App\Http\Controllers\Web\ConsultantController@update'                     => '更新现场咨询',
            'App\Http\Controllers\Web\ConsultantController@create'                     => '创建现场咨询',
            'App\Http\Controllers\Web\ConsultantController@cancel'                     => '取消接待',
            'App\Http\Controllers\Web\CouponController@issue'                          => '购卡换券',
            'App\Http\Controllers\Web\CashierController@charge'                        => '收费操作',
            'App\Http\Controllers\Web\CashierController@erkaiCharge'                   => '二开收费',
            'App\Http\Controllers\Web\CashierController@refundCharge'                  => '退款收费',
            'App\Http\Controllers\Web\CashierController@consultantCharge'              => '现场收费',
            'App\Http\Controllers\Web\CashierController@cancel'                        => '收费退单',
            'App\Http\Controllers\Web\CashierController@recharge'                      => '收费充值',
            'App\Http\Controllers\Web\CashierRetailController@charge'                  => '零售收费',
            'App\Http\Controllers\Web\CashierArrearageController@repayment'            => '还款操作',
            'App\Http\Controllers\Web\FollowupController@execute'                      => '完成回访(PC)',
            'App\Http\Controllers\Api\FollowupController@execute'                  => '完成回访(APP)',
            'App\Http\Controllers\Web\FollowupController@update'                       => '更新回访',
            'App\Http\Controllers\Web\FollowupController@create'                       => '创建回访',
            'App\Http\Controllers\Web\FollowupController@batchInsert'                  => '设置回访规则',
            'App\Http\Controllers\Web\DataMaintenanceController@removeCustomerProduct' => '删除已购项目',
            'App\Http\Controllers\Web\CustomerBatchController@doctor'                  => '批量修改主治医生',
            'App\Http\Controllers\Web\CustomerBatchController@service'                 => '批量修改专属客服',
            'App\Http\Controllers\Web\CustomerBatchController@ascription'              => '批量修改开发人员',
            'App\Http\Controllers\Web\CustomerBatchController@consultant'              => '批量修改销售顾问',
            'App\Http\Controllers\Web\CustomerBatchController@followup'                => '批量设置回访',
            'App\Http\Controllers\Web\CustomerBatchController@tags'                    => '批量设置标签',
            'App\Http\Controllers\Web\CustomerBatchController@removeGroup'             => '批量移除分组',
            'App\Http\Controllers\Web\CustomerBatchController@changeGroup'             => '批量更改分组',
        ]
    ],
    'customer_photo'           => [
        'flag' => [
            'preoperative'  => '术前',
            'postoperative' => '术后',
            'recovery'      => '恢复'
        ]
    ],
    'customer_product'         => [
        'status' => [
            1 => '待划扣',
            2 => '已治疗',
            3 => '已退费',
            4 => '已过期',
            5 => '疗程中',
        ]
    ],
    'customer_goods'           => [
        'status' => [
            1 => '待出库',
            2 => '全部出库',
            3 => '过期',
            4 => '部分出库',
            5 => '退费',
        ]
    ],
    'customer_deposit_details' => [
        'cashierable_type' => [
            'App\Models\Erkai'         => '二开零购',
            'App\Models\Recharge'      => '收费充值',
            'App\Models\Consultant'    => '现场咨询',
            'App\Models\CashierRefund' => '退款收费',
        ]
    ],
    'cc_cdr'                   => [
        'type'   => [
            'Outbound' => '呼出',
            'Inbound'  => '呼入',
            'Internal' => '内部',
        ],
        'status' => [
            'NO ANSWER' => '未接',
            'ANSWERED'  => '已接',
        ]
    ],
    'cc_extension'             => [
        'status' => [
            'unavailable' => '未注册',
            'registered'  => '已注册',
            'ringing'     => '响铃',
            'busy'        => '忙线',
            'hold'        => '通话保持',
            'malfunction' => '故障',
            'idle'        => '空闲',
        ]
    ],
    'department_picking'       => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'erkai'                    => [
        'status' => [
            0 => '未保存',
            1 => '待收费',
            2 => '已成交',
            3 => '已取消'
        ],
    ],
    'erkai_detail'             => [
        'status' => [
            0 => '未保存',
            1 => '待审核',
            2 => '待收费',
            3 => '成交',
            4 => '退单',
            5 => '退费'
        ]
    ],
    'reservation'              => [
        'status' => [
            0 => '未保存',
            1 => '未上门',
            2 => '已到院'
        ]
    ],
    'reception'                => [
        'status'      => [
            0 => '未保存',
            1 => '未成交',
            2 => '已成交'
        ],
        'receptioned' => [
            0 => '否',
            1 => '是'
        ]
    ],
    'reception_order'          => [
        'status' => [
            0 => '未保存',
            1 => '待审核',
            2 => '待收费',
            3 => '成交',
            4 => '退单',
            5 => '退费'
        ]
    ],
    'followup'                 => [
        'status' => [
            1 => '未回访',
            2 => '已回访'
        ]
    ],
    'users_login'              => [
        'type' => [
            1 => '网页端',
            2 => 'APP登陆',
            3 => '扫码登录'
        ]
    ],
    'product'                  => [
        'disabled' => [
            0 => '启用',
            1 => '停用'
        ]
    ],
    'purchase'                 => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'purchase_return'          => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'inventory_loss'           => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'inventory_overflow'       => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'inventory_detail'         => [
        'detailable_type' => [
            'App\Models\Purchase'          => '采购入库',
            'App\Models\Consumable'        => '用料登记',
            'App\Models\PurchaseReturn'    => '进货退货',
            'App\Models\RetailOutbound'    => '零售出料',
            'App\Models\DepartmentPicking' => '科室领料',
            'App\Models\InventoryTransfer' => '库存调拨',
            'App\Models\InventoryLoss'     => '报损单',
            'App\Models\InventoryOverflow' => '报溢单'
        ]
    ],
    'inventory_transfer'       => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'inventory_losses'       => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'inventory_overflows'       => [
        'status' => [
            1 => '草稿',
            2 => '正常'
        ]
    ],
    'appointment'              => [
        'status'      => [
            0 => '待确认',
            1 => '待上门',
            2 => '已到店',
            3 => '已接待',
            4 => '已开单',
            5 => '已治疗',
            6 => '已超时'
        ],
        'type'        => [
            'coming'    => '面诊预约',
            'treatment' => '治疗预约',
            'operation' => '手术预约'
        ],
        'anaesthesia' => [
            'regional' => '局麻',
            'general'  => '全麻'
        ]
    ],
    'integral'                 => [
        'type' => [
            1 => '充值赠送积分',
            2 => '项目消费积分',
            3 => '物品消费积分',
            4 => '积分换券',
            5 => '手工赠送',
            6 => '手工扣减',
            7 => '积分清零',
        ]
    ],
    'outpatient_prescription'  => [
        'status' => [
            1 => '未收费',
            2 => '收款未发药',
            3 => '已发药'
        ],
        'type'   => [
            1 => '普通处方',
            2 => '麻醉处方',
            3 => '精一',
            4 => '精二',
            5 => '中药',
            6 => '毒'
        ]
    ],
    'sms'                      => [
        'type'    => [
            'reservation' => '网电咨询'
        ],
        'status'  => [
            'success' => '发送成功',
            'failure' => '发送失败'
        ],
        'gateway' => [
            'aliyun' => '阿里云'
        ]
    ],
    'sales_performance'        => [
        'position'   => [
            1 => '销售提成',
            2 => '开发人提成',
            3 => '项目服务',
        ],
        'table_name' => [
            'App\Models\Erkai'            => '二开零购',
            'App\Models\Recharge'         => '收费充值',
            'App\Models\Consultant'       => '现场咨询',
            'App\Models\Treatment'        => '治疗划扣',
            'App\Models\Outpatient'       => '医生门诊',
            'App\Models\CashierRefund'    => '退款',
            'App\Models\CashierArrearage' => '还款',
            'App\Models\CashierRetail'    => '零售收费',
            'App\Models\CouponDetail'     => '购卡换券',
        ]
    ],
    'treatment'                => [
        'status' => [
            1 => '正常',
            2 => '撤销',
        ]
    ],
    'personal_access_tokens'   => [
        'tokenable_type' => [
            'App\Models\User'     => '员工令牌',
            'App\Models\Customer' => '顾客令牌',
        ],
        'name'           => [
            'app'    => 'app登录',
            'web'    => 'web登录',
            'wechat' => '小程序登录',
        ]
    ]
];
