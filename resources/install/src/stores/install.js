import { defineStore } from 'pinia'

export const useInstallStore = defineStore('install', {
  state: () => ({
    steps: [],
    currentStep: 0,
    admin_url: ''
  }),
  actions: {
    setSteps(steps) {
      this.steps = steps
    },
    setCurrentStep(step) {
      this.currentStep = step
    },
    setAdminUrl(url) {
      this.admin_url = url
    }
  }
}) 