import msgbox from '@/components/cyMsgbox'
import { ElMessageBox, ElMessage } from 'element-plus'

let currentId = 0

/**
 * 全局唯一id
 * @returns {Number}
 */
export function generateId() {
	return currentId++
}

/**
 * 防抖函数
 * @param {Function} func 要执行的函数
 * @param {Number} delay 延迟时间
 * @returns {Function} 返回一个新的函数
 */
export function debounce(func, delay) {
	let timer = null
	return function (...args) {
		if (timer) {
			clearTimeout(timer)
		}
		timer = setTimeout(() => {
			func.apply(this, args)
		}, delay)
	}
}

/**
 * 消息提示
 * @param {*} options
 */
export function alertMessager(options) {
	if (typeof options === 'string') {
		options = {
			message: options
		}
	}

	const { title = '提示', message, confirmButtonText = '确定', callback } = options

	ElMessageBox.alert(message, title, {
		type: 'warning',
		confirmButtonText,
		callback: (action) => {
			callback && callback(action)
		}
	})
}

/**
 * 确认消息
 * @param {*} options
 */
export function confirmMessager(options) {
	if (typeof options === 'string') {
		options = {
			message: options
		}
	}

	const {
		title = '提示',
		message,
		confirmButtonText = '确定',
		cancelButtonText = '取消',
		callback
	} = options

	ElMessageBox.confirm(message, title, {
		type: 'warning',
		confirmButtonText,
		cancelButtonText,
		callback: (action) => {
			callback && callback(action)
		}
	})
}

/**
 * 对ElMessage二次封装
 * @param {*} message
 * @param {*} type
 * @param {*} options
 */
export function showToast(message, type = 'success', options = {}) {
	ElMessage({
		message,
		type,
		...options
	})
}

/**
 * 提示框
 * @returns
 */
export function useMsgbox() {
	return {
		loading: msgbox.loading,
		close: msgbox.close
	}
}

/**
 * 复制文本到剪贴板
 * @param {*} text
 * @returns
 */
export function copyToClipboard(text) {
	// 优先使用现代 API
	if (navigator.clipboard && window.isSecureContext) {
		return navigator.clipboard.writeText(text)
	} else {
		// 降级到传统方法
		const textarea = document.createElement('textarea')
		textarea.value = text
		document.body.appendChild(textarea)
		textarea.select()
		document.execCommand('copy')
		document.body.removeChild(textarea)
		return Promise.resolve()
	}
}
