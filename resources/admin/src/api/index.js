/**
 * @description 自动import导入所有 api 模块
 */
const files = import.meta.glob('./model/*.js', { eager: true })
const modules = {}
Object.keys(files).forEach(async (key) => {
	const pathName = key.replace(/(\.\/|\.js)/g, '')
	const name = pathName.replace('model/', '')
	modules[name] = files[key].default
})

export default modules
