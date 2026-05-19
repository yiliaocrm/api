import { ref, watch, onMounted, onBeforeUnmount, onActivated, onDeactivated } from 'vue'

/**
 * 自动轮询 composable
 * @param {Function} fn - 要轮询执行的函数
 * @param {number} interval - 轮询间隔（毫秒），默认 5000
 * @param {boolean} immediate - 是否立即执行一次，默认 true
 */
export function usePolling(fn, interval = 5000, immediate = true) {
	let timer = null
	const loading = ref(false)
	const autoRefresh = ref(true)

	const execute = async () => {
		loading.value = true
		try {
			await fn()
		} finally {
			loading.value = false
		}
	}

	const start = () => {
		stop()
		timer = setInterval(execute, interval)
	}

	const stop = () => {
		if (timer) {
			clearInterval(timer)
			timer = null
		}
	}

	const handleVisibilityChange = () => {
		if (document.hidden) {
			stop()
		} else if (autoRefresh.value) {
			execute()
			start()
		}
	}

	watch(autoRefresh, (val) => {
		if (val) {
			execute()
			start()
		} else {
			stop()
		}
	})

	onMounted(async () => {
		if (immediate) {
			await execute()
		}
		start()
		document.addEventListener('visibilitychange', handleVisibilityChange)
	})

	onDeactivated(() => {
		stop()
	})

	onActivated(() => {
		if (autoRefresh.value) {
			execute()
			start()
		}
	})

	onBeforeUnmount(() => {
		stop()
		document.removeEventListener('visibilitychange', handleVisibilityChange)
	})

	return { loading, autoRefresh, execute, start, stop }
}
