import { createApp, h, nextTick } from 'vue'
import Msgbox from './msgbox'

let instance = null // 单例

async function message(content, duration) {
	closeMessage()

	const mountPoint = document.createElement('div')
	document.body.appendChild(mountPoint)

	const app = createApp({
		components: {
			Msgbox
		},
		data() {
			return {
				content: content,
				duration: duration
			}
		},
		methods: {
			close() {
				if (this.$refs.msgboxRef) {
					this.$refs.msgboxRef.hide()
				}
			}
		},
		render() {
			return h(Msgbox, {
				ref: 'msgboxRef',
				content: this.content,
				duration: this.duration
			})
		}
	})

	instance = app.mount(mountPoint)

	await nextTick()
	if (instance && instance.$refs && instance.$refs.msgboxRef) {
		instance.$refs.msgboxRef.show()
	}
}

function closeMessage() {
	if (instance && instance.$refs && instance.$refs.msgboxRef) {
		instance.$refs.msgboxRef.hide()
	}
	if (instance && instance.$el && instance.$el.parentNode) {
		instance.$el.parentNode.removeChild(instance.$el)
	}
	instance = null
}

export default {
	loading(content = '数据加载中', duration = 3000) {
		return message(content, duration)
	},
	close: closeMessage
}
