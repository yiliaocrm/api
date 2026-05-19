<template>
	<div class="cywebos-login-wrapper login_bg">
		<div class="cywebos-login-inner">
			<div class="cywebos-login-left">
				<el-carousel :interval="5000" height="500px" arrow="always">
					<el-carousel-item v-for="(banner, index) in banners" :key="index">
						<img :src="banner.src" />
					</el-carousel-item>
				</el-carousel>
			</div>
			<div class="cywebos-login-right">
				<header class="cywebos-login-header">
					<h3 class="cywebos-login-title">蝉印诊所管家运营平台</h3>
				</header>
				<main class="cywebos-login-main">
					<el-form
						ref="formRef"
						:model="form"
						:rules="rules"
						hide-required-asterisk
						label-width="auto"
						@keyup.enter="handleLogin"
					>
						<el-form-item prop="username" label="账号:">
							<el-input
								v-model.trim="form.username"
								prefix-icon="el-icon-user"
								clearable
								placeholder="请输入账号"
							></el-input>
						</el-form-item>
						<el-form-item prop="password" label="密码:">
							<el-input
								v-model="form.password"
								prefix-icon="el-icon-lock"
								clearable
								show-password
								placeholder="请输入密码"
							></el-input>
						</el-form-item>
						<el-form-item v-if="showCode" prop="code" label="口令:">
							<el-input
								v-model.trim="form.code"
								prefix-icon="sc-icon-code"
								clearable
								placeholder="请输入动态口令"
							></el-input>
						</el-form-item>
						<el-form-item>
							<el-button
								type="primary"
								style="width: 100%"
								:loading="loading"
								@click="handleLogin"
								>{{ loading ? '登录中' : '登录' }}</el-button
							>
						</el-form-item>
					</el-form>
				</main>
				<footer class="cywebos-login-footer">
					<el-text>系统版本号:{{ version }}</el-text>
					<a href="http://www.yiliaocrm.com" target="_blank">访问官网</a>
				</footer>
			</div>
		</div>
		<div class="login_config">
			<el-button
				:icon="dark ? 'el-icon-sunny' : 'el-icon-moon'"
				circle
				type="info"
				@click="toggleDark"
			></el-button>
		</div>
	</div>
</template>

<script setup>
	import Api from '@/api/index.js'
	import tool from '@/utils/tool.js'
	import FingerprintJS from '@fingerprintjs/fingerprintjs'
	import { useStore } from 'vuex'
	import { useRouter } from 'vue-router'
	import { ElMessage } from 'element-plus'
	import { ref, watch, onMounted } from 'vue'

	defineOptions({
		name: 'LoginIndex'
	})

	const store = useStore()
	const router = useRouter()
	const formRef = ref(null)

	const form = ref({
		username: null,
		password: null,
		code: null,
		fingerprint: null
	})

	const rules = ref({
		username: [{ required: true, message: '账号不能为空!', trigger: 'blur' }],
		password: [{ required: true, message: '密码不能为空!', trigger: 'blur' }],
		code: [{ required: false, message: '口令不能为空!', trigger: 'blur' }]
	})

	const dark = ref(false)
	const loading = ref(false)
	const banners = ref([
		{
			src: '/static/images/banner2.jpg'
		}
	])
	const version = ref('1.0.0')
	const showCode = ref(false)

	watch(dark, (val) => {
		if (val) {
			document.documentElement.classList.add('dark')
		} else {
			document.documentElement.classList.remove('dark')
		}
	})

	onMounted(async () => {
		// 获取配置
		await fetchConfig()

		// 删除
		tool.session.remove('token')
		tool.session.remove('USER_INFO')
		tool.session.remove('MENU')
		tool.session.remove('PERMISSIONS')
		tool.session.remove('DASHBOARDGRID')
		tool.session.remove('grid')
		store.commit('clearViewTags')
		store.commit('clearKeepLive')
		store.commit('clearIframeList')

		// 获取指纹
		const fp = await FingerprintJS.load()
		const result = await fp.get()
		form.value.fingerprint = result.visitorId
	})

	const fetchConfig = async () => {
		let { data, code } = await Api.auth.config.get()
		if (data && code == 200) {
			version.value = data.his_version
			showCode.value = data.central_login_tfa
			// 开启2fa登录
			if (showCode.value) {
				rules.value.code[0].required = true
				// 清空表单验证
				setTimeout(() => {
					formRef.value.clearValidate()
				}, 200)
			}
		}
	}

	const handleLogin = () => {
		formRef.value.validate(async (valid) => {
			if (valid) {
				loading.value = true
				let { data, code } = await Api.auth.login.post(form.value)
				if (data && code == 200) {
					loginSuccess(data.access_token)
				}
				loading.value = false
			}
		})
	}

	const loginSuccess = (token) => {
		tool.session.set('token', token)

		// 跳转到后台
		router.replace({
			path: '/'
		})

		ElMessage.success('登录成功')
	}

	const toggleDark = () => {
		dark.value = !dark.value
	}
</script>

<style>
	.cywebos-login-wrapper {
		background-color: #fff;
		background-image: url('@/assets/images/login_bg.png');
		background-repeat: no-repeat;
		background-size: cover;
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.cywebos-login-inner {
		width: 1000px;
		height: 500px;
		background: #fff;
		border-radius: 10px;
		box-shadow: 0 30px 60px 5px rgb(19 39 92 / 30%);
		display: flex;
	}

	.cywebos-login-left {
		width: 600px;
	}

	.cywebos-login-left img {
		width: 100%;
		object-fit: cover;
		border-radius: 10px 0 0 10px;
	}

	.cywebos-login-right {
		position: relative;
		border-left: 1px solid #dedede;
		flex: 1;
		display: flex;
		flex-direction: column;
	}

	.cywebos-login-header {
		padding: 0 40px;
	}

	.cywebos-login-toggle {
		width: 70px;
		height: 70px;
		position: absolute;
		top: 0;
		right: 0;
		cursor: pointer;
		background-image: url('@/assets/images/login_toggle_qrcode.png');
		background-size: cover;
		background-repeat: no-repeat;
	}

	.cywebos-login-toggle.pc {
		background-image: url('@/assets/images/login_toggle_pc.png');
	}

	.cywebos-login-title {
		color: #0d0d0d;
		font-size: 30px;
		font-weight: 600;
		padding: 55px 0 40px 0;
		margin: 0;
	}

	.cywebos-login-main {
		flex: 1;
		padding: 0 40px;
		display: flex;
		flex-direction: column;
	}

	.cywebos-login-qr-wrapper {
		min-height: 265px;
		text-align: center;
	}

	.cywebos-login-qr-loading {
		background: url('@/assets/images/loading.gif') center center no-repeat;
	}

	.cywebos-login-qr-expire {
		background: url('@/assets/images/qr_expire.png') center center no-repeat;
	}

	.cywebos-login-refresh-qrcode {
		color: #2878ff;
		cursor: pointer;
		display: block;
		font-size: 14px;
		line-height: 35px;
		text-align: center;
		text-decoration: underline;
	}

	.cywebos-login-qrcode-description {
		margin-top: 10px;
		text-align: center;
	}

	.cywebos-login-qrcode-description .app,
	.cywebos-login-footer a {
		color: #2878ff;
		cursor: pointer;
		font-size: 14px;
		text-decoration: none;
	}

	.cywebos-login-footer {
		height: 60px;
		line-height: 60px;
		padding: 0 40px;
		border-top: 1px solid #f5f5f5;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.login_config {
		position: absolute;
		top: 20px;
		right: 20px;
	}
</style>
