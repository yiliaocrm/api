import { reactive } from 'vue'

/**
 * Horizon 游标分页 composable
 * Horizon 使用 starting_at 游标而非传统 page/limit 分页
 */
export function useCursorPagination() {
	const pagination = reactive({
		startingAt: -1,
		total: 0,
		hasMore: false
	})

	const reset = () => {
		pagination.startingAt = -1
		pagination.total = 0
		pagination.hasMore = false
	}

	const update = (jobs, total) => {
		pagination.total = total
		if (jobs && jobs.length > 0) {
			const lastJob = jobs[jobs.length - 1]
			pagination.hasMore = jobs.length >= 50
			// 记录最后一个 job 的 index 用于下一页
			if (lastJob.index !== undefined) {
				pagination.startingAt = lastJob.index
			}
		} else {
			pagination.hasMore = false
		}
	}

	return { pagination, reset, update }
}
