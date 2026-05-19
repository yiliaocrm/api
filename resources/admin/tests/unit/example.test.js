import { describe, it, expect } from 'vitest'

describe('示例测试', () => {
  it('基本断言：1 + 1 = 2', () => {
    expect(1 + 1).toBe(2)
  })

  it('对象相等', () => {
    const obj = { name: '测试', value: 100 }
    expect(obj).toEqual({ name: '测试', value: 100 })
  })

  it('数组包含', () => {
    const arr = ['vue', 'vitest', 'element-plus']
    expect(arr).toContain('vitest')
  })

  it('真值判断', () => {
    expect(true).toBeTruthy()
    expect(0).toBeFalsy()
    expect(null).toBeNull()
    expect(undefined).toBeUndefined()
  })
})
