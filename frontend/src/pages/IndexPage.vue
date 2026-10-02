<template>
  <q-page class="task-page">
    <div class="page-container">

      <!-- Header -->
      <div class="page-header">
        <div>
          <div class="eyebrow">PRODUCTIVITY</div>
          <h1>My Tasks</h1>
          <p>Stay organized and keep your work moving.</p>
        </div>

        <q-btn
          color="primary"
          unelevated
          no-caps
          icon="add"
          label="Add Task"
          class="add-button"
          @click="openCreateDialog"
        />
      </div>

      <!-- Statistics -->
      <div class="stats-grid">

        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="stat-label">All Tasks</div>
            <div class="stat-number">
              {{ taskStore.totalTasks }}
            </div>
          </q-card-section>
        </q-card>

        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="stat-label">Pending</div>
            <div class="stat-number">
              {{ taskStore.pendingTasks }}
            </div>
          </q-card-section>
        </q-card>

        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="stat-label">Completed</div>
            <div class="stat-number">
              {{ taskStore.completedTasks }}
            </div>
          </q-card-section>
        </q-card>

      </div>

      <!-- Filters -->
      <q-card flat bordered class="filter-card">
        <q-card-section class="filter-row">

          <q-input
            v-model="search"
            outlined
            dense
            placeholder="Search tasks..."
            class="search-input"
            clearable
          >
            <template #prepend>
              <q-icon name="search" />
            </template>
          </q-input>

          <q-select
            v-model="statusFilter"
            outlined
            dense
            emit-value
            map-options
            :options="statusOptions"
            label="Status"
            class="filter-select"
          />

          <q-select
            v-model="priorityFilter"
            outlined
            dense
            emit-value
            map-options
            :options="priorityOptions"
            label="Priority"
            class="filter-select"
          />

        </q-card-section>
      </q-card>

      <!-- Loading -->
      <div v-if="taskStore.loading" class="loading-container">
        <q-spinner color="primary" size="40px" />
        <div>Loading tasks...</div>
      </div>

      <!-- Error -->
      <q-banner
        v-else-if="taskStore.error"
        rounded
        class="error-banner"
      >
        <template #avatar>
          <q-icon name="error_outline" />
        </template>

        {{ taskStore.error }}

        <template #action>
          <q-btn
            flat
            label="Try Again"
            @click="loadTasks"
          />
        </template>
      </q-banner>

      <!-- Empty -->
      <div
        v-else-if="filteredTasks.length === 0"
        class="empty-state"
      >
        <q-icon
          name="task_alt"
          size="70px"
          color="grey-5"
        />

        <h2>No tasks found</h2>

        <p>
          {{
            search || statusFilter || priorityFilter
              ? 'Try changing your filters.'
              : 'Create your first task to get started.'
          }}
        </p>

        <q-btn
          v-if="!search && !statusFilter && !priorityFilter"
          color="primary"
          unelevated
          no-caps
          icon="add"
          label="Create Task"
          @click="openCreateDialog"
        />
      </div>

      <!-- Tasks -->
      <div v-else class="task-list">

        <q-card
          v-for="task in filteredTasks"
          :key="task.id"
          flat
          bordered
          class="task-card"
          :class="{ completed: task.status === 'completed' }"
        >

          <q-card-section>

            <div class="task-content">

              <q-checkbox
                :model-value="task.status === 'completed'"
                color="primary"
                @update:model-value="toggleTask(task)"
              />

              <div class="task-information">

                <div class="task-title-row">
                  <h3>{{ task.title }}</h3>

                  <q-badge
                    :color="priorityColor(task.priority)"
                    :label="capitalize(task.priority)"
                  />
                </div>

                <p
                  v-if="task.description"
                  class="task-description"
                >
                  {{ task.description }}
                </p>

                <div class="task-meta">

                  <span
                    v-if="task.due_date"
                    class="meta-item"
                  >
                    <q-icon name="event" size="16px" />
                    {{ formatDate(task.due_date) }}
                  </span>

                  <span class="meta-item">
                    <q-icon
                      :name="
                        task.status === 'completed'
                          ? 'check_circle'
                          : 'schedule'
                      "
                      size="16px"
                    />

                    {{
                      task.status === 'completed'
                        ? 'Completed'
                        : 'Pending'
                    }}
                  </span>

                </div>

              </div>

              <div class="task-actions">

  <q-btn
    flat
    round
    dense
    icon="edit"
    color="grey-7"
    @click="openEditDialog(task)"
  >
    <q-tooltip>Edit task</q-tooltip>
  </q-btn>

  <q-btn
    flat
    round
    dense
    icon="delete"
    color="negative"
    @click.stop="confirmDelete(task)"
  >
    <q-tooltip>Delete task</q-tooltip>
  </q-btn>

</div>

</div>

</q-card-section>

</q-card>

</div>

</div>

    <!-- Add/Edit Dialog -->
    <q-dialog v-model="dialogOpen">
      <q-card class="task-dialog">

        <q-card-section>
          <div class="dialog-title">
            {{ editingTask ? 'Edit Task' : 'Add Task' }}
          </div>

          <div class="dialog-subtitle">
            {{
              editingTask
                ? 'Update your task details.'
                : 'Create a new task.'
            }}
          </div>
        </q-card-section>

        <q-form @submit="saveTask">

          <q-card-section class="q-pt-none">

            <q-input
              v-model="form.title"
              outlined
              label="Task title *"
              :rules="[
                (value) => !!value || 'Task title is required',
              ]"
              class="form-field"
            />

            <q-input
              v-model="form.description"
              outlined
              type="textarea"
              label="Description"
              rows="3"
              class="form-field"
            />

            <q-input
              v-model="form.due_date"
              outlined
              type="date"
              label="Due date"
              class="form-field"
            />

            <q-select
              v-model="form.priority"
              outlined
              emit-value
              map-options
              :options="priorityFormOptions"
              label="Priority"
              class="form-field"
            />

            <q-select
              v-if="editingTask"
              v-model="form.status"
              outlined
              emit-value
              map-options
              :options="statusFormOptions"
              label="Status"
              class="form-field"
            />

          </q-card-section>

          <q-card-actions align="right" class="q-pa-md">

            <q-btn
              flat
              no-caps
              label="Cancel"
              v-close-popup
            />

            <q-btn
              color="primary"
              unelevated
              no-caps
              type="submit"
              :loading="saving"
              :label="editingTask ? 'Update Task' : 'Create Task'"
            />

          </q-card-actions>

        </q-form>

      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useTaskStore } from '../stores/task-store'
import type { Task } from '../stores/task-store'

const $q = useQuasar()
const taskStore = useTaskStore()

const search = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')

const dialogOpen = ref(false)
const saving = ref(false)
const editingTask = ref<Task | null>(null)

const form = reactive({
  title: '',
  description: '',
  due_date: '',
  priority: 'medium' as 'low' | 'medium' | 'high',
  status: 'pending' as 'pending' | 'completed',
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
]

const priorityOptions = [
  { label: 'All Priorities', value: '' },
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
]

const priorityFormOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
]

const statusFormOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
]

const filteredTasks = computed(() => {
  return taskStore.tasks.filter((task) => {
    const searchText = search.value.toLowerCase()

    const matchesSearch =
      !searchText ||
      task.title.toLowerCase().includes(searchText) ||
      (task.description ?? '').toLowerCase().includes(searchText)

    const matchesStatus =
      !statusFilter.value ||
      task.status === statusFilter.value

    const matchesPriority =
      !priorityFilter.value ||
      task.priority === priorityFilter.value

    return matchesSearch && matchesStatus && matchesPriority
  })
})

async function loadTasks() {
  await taskStore.fetchTasks()
}

function resetForm() {
  form.title = ''
  form.description = ''
  form.due_date = ''
  form.priority = 'medium'
  form.status = 'pending'
}

function openCreateDialog() {
  editingTask.value = null
  resetForm()
  dialogOpen.value = true
}

function openEditDialog(task: Task) {
  editingTask.value = task

  form.title = task.title
  form.description = task.description ?? ''
  form.due_date = task.due_date ?? ''
  form.priority = task.priority
  form.status = task.status

  dialogOpen.value = true
}

async function saveTask() {
  try {
    const wasEditing = !!editingTask.value

    if (editingTask.value) {
      await taskStore.updateTask(editingTask.value.id, {
        title: form.title,
        description: form.description,
        due_date: form.due_date,
        priority: form.priority,
        status: form.status,
      })
    } else {
      await taskStore.addTask({
        title: form.title,
        description: form.description,
        due_date: form.due_date,
        priority: form.priority,
        status: form.status,
      })
    }

    // Close the card/dialog immediately
dialogOpen.value = false
editingTask.value = null

    $q.notify({
      type: 'positive',
      message: wasEditing
        ? 'Task updated successfully'
        : 'Task created successfully',
    })
  } catch (error) {
    console.error(error)

    $q.notify({
      type: 'negative',
      message: 'Failed to save task',
    })
  }
}

async function toggleTask(task: Task) {
  try {
    await taskStore.toggleTask(task.id)
  } catch (error) {
    console.error(error)

    $q.notify({
      type: 'negative',
      message: 'Unable to update task.',
    })
  }
}

async function confirmDelete(task: Task) {
  const confirmed = window.confirm(
    `Are you sure you want to delete "${task.title}"?`
  )

  if (!confirmed) {
    return
  }

  try {
    await taskStore.deleteTask(task.id)

    $q.notify({
      type: 'positive',
      message: 'Task deleted successfully.',
    })
  } catch (error) {
    console.error('Delete error:', error)

    $q.notify({
      type: 'negative',
      message: 'Unable to delete task.',
    })
  }
}

function priorityColor(priority: Task['priority']) {
  if (priority === 'high') return 'negative'
  if (priority === 'medium') return 'warning'
  return 'positive'
}

function capitalize(value: string) {
  return value.charAt(0).toUpperCase() + value.slice(1)
}

function formatDate(date: string | null) {
  if (!date) {
    return ''
  }

  // Handle both YYYY-MM-DD and Laravel timestamp formats
  const normalizedDate =
    /^\d{4}-\d{2}-\d{2}$/.test(date)
      ? `${date}T00:00:00`
      : date

  const parsedDate = new Date(normalizedDate)

  if (Number.isNaN(parsedDate.getTime())) {
    return 'Invalid Date'
  }

  return parsedDate.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

onMounted(() => {
  void loadTasks()
})
</script>

<style lang="scss" scoped>
.task-page {
  background: #f7f7fb;
  min-height: 100vh;
}

.page-container {
  width: min(1100px, calc(100% - 32px));
  margin: 0 auto;
  padding: 40px 0 60px;
}

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 28px;
}

.eyebrow {
  color: #7c3aed;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.5px;
  margin-bottom: 5px;
}

h1 {
  margin: 0;
  font-size: 34px;
  font-weight: 800;
  letter-spacing: -1px;
}

.page-header p {
  color: #737681;
  margin: 7px 0 0;
}

.add-button {
  border-radius: 10px;
  padding: 5px 14px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 18px;
}

.stat-card {
  border-radius: 14px;
  background: white;
}

.stat-label {
  color: #777b87;
  font-size: 14px;
}

.stat-number {
  font-size: 30px;
  font-weight: 800;
  margin-top: 4px;
}

.filter-card {
  border-radius: 14px;
  margin-bottom: 18px;
}

.filter-row {
  display: flex;
  gap: 12px;
}

.search-input {
  flex: 1;
}

.filter-select {
  width: 180px;
}

.task-list {
  display: grid;
  gap: 12px;
}

.task-card {
  border-radius: 14px;
  transition: transform 0.15s ease;
}

.task-card:hover {
  transform: translateY(-1px);
}

.task-card.completed {
  opacity: 0.7;
}

.task-card.completed h3 {
  text-decoration: line-through;
}

.task-content {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.task-information {
  flex: 1;
  min-width: 0;
}

.task-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.task-title-row h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 750;
}

.task-description {
  color: #737681;
  margin: 7px 0 10px;
  white-space: pre-wrap;
}

.task-meta {
  display: flex;
  gap: 16px;
  color: #7b7f89;
  font-size: 13px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.task-actions {
  display: flex;
  gap: 2px;
}

.loading-container,
.empty-state {
  text-align: center;
  padding: 55px 20px;
}

.empty-state {
  background: white;
  border: 1px dashed #d7d9df;
  border-radius: 14px;
}

.empty-state h2 {
  margin: 12px 0 5px;
}

.empty-state p {
  color: #777b87;
  margin-bottom: 18px;
}

.error-banner {
  background: #fff1f2;
  color: #991b1b;
}

.task-dialog {
  width: 500px;
  max-width: 90vw;
  border-radius: 16px;
}

.dialog-title {
  font-size: 22px;
  font-weight: 800;
}

.dialog-subtitle {
  color: #777b87;
  margin-top: 4px;
}

.form-field {
  margin-bottom: 16px;
}

@media (max-width: 700px) {
  .page-header {
    align-items: stretch;
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filter-row {
    flex-direction: column;
  }

  .filter-select {
    width: 100%;
  }

  .task-content {
    flex-wrap: wrap;
  }

  .task-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
