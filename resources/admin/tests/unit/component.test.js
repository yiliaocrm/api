import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, h } from 'vue'

// 示例：测试一个简单的 Vue 组件
const HelloWorld = defineComponent({
  name: 'HelloWorld',
  props: {
    msg: {
      type: String,
      default: '',
    },
  },
  setup(props) {
    return () => h('div', { class: 'hello' }, [h('h1', props.msg)])
  },
})

describe('Vue 组件测试示例', () => {
  it('正确渲染 props.msg', () => {
    const wrapper = mount(HelloWorld, {
      props: {
        msg: '你好，Vitest！',
      },
    })
    expect(wrapper.text()).toContain('你好，Vitest！')
  })

  it('包含 .hello 类名', () => {
    const wrapper = mount(HelloWorld)
    expect(wrapper.classes()).toContain('hello')
  })

  it('默认 msg 为空字符串', () => {
    const wrapper = mount(HelloWorld)
    expect(wrapper.find('h1').text()).toBe('')
  })
})
