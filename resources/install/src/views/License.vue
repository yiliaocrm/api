<template>
  <div class="license-container">
    <div class="license-content">
      <h3>软件许可协议</h3>
      <div class="license-text">
        <p><storage>重要提示：</storage></p>
        <p>在安装、使用本软件（指【蝉印诊所管家】，以下简称“本软件”）之前，请您仔细阅读并理解本协议的全部内容。您的安装、使用、复制或分发本软件的行为，即视为您已充分阅读、理解并同意接受本协议全部条款的约束。若您不同意本协议的任何条款，请立即停止使用并销毁本软件的所有副本。</p>
        <p>本协议是您（包括个人、法人或其他组织，以下简称“用户”）与 <strong>【石狮市蝉印网络科技有限公司】</strong>（以下简称“版权方”）之间关于使用本软件所订立的法律协议。</p>
        <p><strong>1.软件版权</strong></p>
        <p>1.1 本软件由版权方独立开发，版权方依法拥有本软件的完整著作权、所有权及相关知识产权。</p>
        <p>1.2 本着技术共享与学习交流的原则，版权方以“源码可见”的形式发布本软件，用户可在严格遵守本协议条款的前提下使用本软件。</p>
        <p><strong>2. 许可范围</strong></p>
        <p>2.1 <strong>学习与研究：</strong>允许用户出于个人学习、内部研究或功能评估的目的，在任意数量的设备上安装、运行、查看和修改本软件的源代码。基于此目的所做的修改无需对外公开。</p>
        <p>2.2 <strong>保留权利：</strong>除本协议明确授予的权利外，版权方保留所有其他权利。未经版权方明确的商业授权，用户不享有本软件的任何商业使用权、分销权、再许可权或其他未明确授予的权利。</p>
        <p><strong>3. 限制条款</strong></p>
        <p>用户不得从事以下任何行为：</p>
        <p>3.1<strong>商业用途：未经版权方书面商业授权，禁止将本软件（包括其源代码、衍生版本）用于任何商业目的。</strong>商业用途包括但不限于： </p>
        <p>（a）将本软件用于企业或组织的内部生产环境、商业化运营或管理活动。</p>
        <p>（b）基于本软件直接或间接获取任何形式的货币或非货币收益。 </p>
        <p>（c）将本软件（无论是否经过修改）作为其他商业产品或服务的一部分进行销售、出租、许可或分发。</p>
        <p>3.2 <strong>分发衍生版本：</strong>未经版权方明确的<strong>商业授权</strong>，禁止在本软件整体或部分基础上，开发、分发任何派生版本、修改版本或第三方版本用于商业目的。个人学习和研究目的的修改不受此限，但不得公开发布或用于商业用途。</p>
        <p>3.3 <strong>移除标识：</strong>禁止移除、隐藏或修改本软件界面、源代码及文档中包含的任何版权声明、商标、水印、链接等版权方标志。</p>
        <p>4. <strong>免责声明</strong></p>
        <p>4.1 本软件按“原样”提供，不附带任何形式的保证。版权方不对其适用性、稳定性、安全性、准确性或无错误作出任何明示或默示的担保。用户使用本软件所产生的全部风险及一切后果由用户自行承担。</p>
        <p>4.2 在用户未购买商业授权与技术支持服务的情况下，版权方无义务为免费用户提供任何形式的技术支持、维护或服务承诺。</p>
        <p>5. <strong>违约与终止</strong></p>
        <p>5.1 若用户违反本协议的任何条款，本协议将自动终止，用户依据本协议获得的所有权利将被立即收回。</p>
        <p>5.2 协议终止后，用户必须立即停止一切使用行为，并销毁本软件的所有副本及相关材料。版权方保留追究相应法律责任的权利。</p>
        <p>6. <strong>商业授权</strong></p>
        <p>6.1 我们提供多种类型的商业授权以满足不同场景的需求，包括但不限于：<strong>企业内部使用授权、二次开发与SaaS服务授权、衍生产品分发授权等。</strong></p>
        <p>6.2 如果您希望将本软件用于本协议所限制的商业用途，您必须从版权方购买相应的商业授权。具体授权范围、权利、限制及费用由双方另行签署的<strong>《商业授权协议》</strong>约定。</p>
        <p>7. <strong>其他</strong></p>
        <p>7.1 版权方有权随时修订本协议。修订后的协议将在官方渠道公布后立即生效。用户继续使用本软件将被视为接受修订后的协议。</p>
        <p>7.2 本协议的解释、效力及纠纷的解决，均适用于中华人民共和国法律。因本协议产生的任何争议，应提交至版权方所在地有管辖权的人民法院诉讼解决。</p>
        <p><strong>版权方信息</strong></p>
        <p>公司名称：【石狮市蝉印网络科技有限公司】</p>
        <p>官方网站：【<a href="http://www.yiliaocrm.com" target="_blank">http://www.yiliaocrm.com</a>】</p>
        <p>联系邮箱：【<a href="mailto:service@yiliaocrm.com">service@yiliaocrm.com</a>】</p>
      </div>
    </div>
    <div class="license-actions">
      <label class="checkbox-label">
        <input type="checkbox" v-model="accepted" />
        我已阅读并同意许可协议
      </label>
      <button class="btn btn--primary" :disabled="!accepted" @click="handleNext">下一步</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const accepted = ref(false)

const handleNext = () => {
  router.push('/environment')
}
</script>

<style scoped>
.license-container {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.license-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  background-color: #f8f9fa;
  border-radius: 4px;
  margin-bottom: 20px;
  overflow: hidden;
}

.license-content h3 {
  margin: 0;
  padding-bottom: 15px;
  background-color: #fff;
}

.license-text {
  height: 450px;
  padding: 0 10px;
  overflow-y: auto;
  border: 1px solid #e4e7ed;
}

.license-text p {
  margin: 10px 0;
  line-height: 1.6;
  color: #606266;
}
.license-text p storage {
  color: #f56c6c;
  font-weight: bold;
}
.license-text p a {
  color: #409eff;
  text-decoration: none;
}

.license-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0 0 0;
  border-top: 1px solid #e4e7ed;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #606266;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #409eff;
}

.btn {
  padding: 8px 20px;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  cursor: pointer;
  font-size: 14px;
  background: #fff;
  color: #606266;
  transition: all .2s;
}

.btn:hover { border-color: #409eff; color: #409eff; }
.btn--primary { background: #409eff; border-color: #409eff; color: #fff; }
.btn--primary:hover { background: #66b1ff; border-color: #66b1ff; }
.btn--primary:disabled { background: #a0cfff; border-color: #a0cfff; cursor: not-allowed; }
</style>