import { defineStore } from 'pinia'
import { api } from '../boot/axios'

export interface Task {
  id: number
  title: string
  description: string | null
  due_date: string | null
  priority: 'low' | 'medium' | 'high'
  status: 'pending' | 'completed'
  created_at: string
  updated_at: string
}

export interface TaskForm {
  title: string
  description: string
  due_date: string
  priority: 'low' | 'medium' | 'high'
  status?: 'pending' | 'completed'
}

export const useTaskStore = defineStore('tasks', {
  state: () => ({
    tasks: [] as Task[],
    loading: false,
    error: null as string | null,
  }),

  getters: {
    totalTasks: (state) => state.tasks.length,

    pendingTasks: (state) =>
      state.tasks.filter((task) => task.status === 'pending').length,

    completedTasks: (state) =>
      state.tasks.filter((task) => task.status === 'completed').length,
  },

  actions: {
    async fetchTasks() {
      this.loading = true
      this.error = null

      try {
        const response = await api.get('/tasks')
        this.tasks = response.data
      } catch (error) {
        console.error(error)
        this.error = 'Unable to connect to the Laravel server.'
      } finally {
        this.loading = false
      }
    },

    async addTask(data: TaskForm) {
      const response = await api.post('/tasks', data)

      this.tasks.unshift(response.data.task)

      return response.data.task
    },

    async updateTask(id: number, data: TaskForm) {
      const response = await api.put(`/tasks/${id}`, data)

      const index = this.tasks.findIndex((task) => task.id === id)

      if (index !== -1) {
        this.tasks[index] = response.data.task
      }

      return response.data.task
    },

    async deleteTask(id: number) {
      await api.delete(`/tasks/${id}`)

      this.tasks = this.tasks.filter((task) => task.id !== id)
    },

    async toggleTask(id: number) {
      const response = await api.patch(`/tasks/${id}/toggle`)

      const index = this.tasks.findIndex((task) => task.id === id)

      if (index !== -1) {
        this.tasks[index] = response.data.task
      }
    },
  },
})