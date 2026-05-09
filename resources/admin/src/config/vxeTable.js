export default {
	size: 'mini', // 表格尺寸
	border: true, // 表格边框
	successCode: 200, //请求完成代码
	pageSize: 10, //表格每一页条数
	pageSizes: [10, 20, 30, 40, 50], //表格可设置的一页条数
	paginationLayout: 'total, sizes, prev, pager, next, jumper', //表格分页布局，可设置"total, sizes, prev, pager, next, jumper"
	parseData: function (res) {
		//数据分析
		return {
			data: res.data, //分析无分页的数据字段结构
			rows: res.data.rows, //分析行数据字段结构
			total: res.data.total, //分析总数字段结构
			footer: res.data.footer, //分析合计行字段结构
			msg: res.message, //分析描述字段结构
			code: res.code //分析状态字段结构
		}
	},
	request: {
		//请求规定字段
		page: 'page', //规定当前分页字段
		pageSize: 'rows', //规定一页条数字段
		sort: 'sort', //规定排序字段名字段
		order: 'order' //规定排序规格字段
	}
}
