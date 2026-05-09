import VXETable from 'vxe-table'

/**
 * 自定义渲染器 - 客户
 */
VXETable.renderer.add('Customer', {
    renderDefault(renderOpts, params) {
        let { events } = renderOpts
        let { row, column } = params
        let value = column.field.split('.').reduce((obj, key) => obj[key], row)
        return <el-link type="primary" onClick={() => events.click(params)}>{value}</el-link>
    }
})