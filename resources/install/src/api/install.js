import request from '@/utils/request'

export function getInstallEnvironment() {
    return request({
        url: '/environment',
        method: 'get'
    })
}

export function startInstall(data) {
    return request({
        url: '/start',
        method: 'post',
        data
    })
}

export function installStep(action) {
    return request({
        url: '/install',
        method: 'get',
        params: {
            action
        }
    })
}

export function getInstallConfig() {
    return request({
        url: '/config',
        method: 'get'
    })
}
