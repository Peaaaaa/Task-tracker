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

        <q-card
          flat
          bordered
          class="stat-card overdue-stat-card cursor-pointer"
          clickable
          @click="showOverdueTasks"
        >
          <q-card-section>
            <div class="stat-label">Overdue</div>

            <div class="stat-number text-negative">
              {{ overdueTasks }}
            </div>

            <div class="text-caption text-negative">
              Tasks past their due date
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Productivity Progress -->
      <q-card flat bordered class="progress-card q-mb-md">
        <q-card-section>
          <div class="row items-center justify-between q-mb-sm">
            <div>
              <div class="text-subtitle1 text-weight-bold">
                Your Productivity
              </div>
              <div class="text-caption text-grey-6">
                Keep making progress, one task at a time.
              </div>
            </div>

            <div class="progress-percentage"> {{ completionPercentage }}% </div>
          </div>

          <q-linear-progress
            :value="completionPercentage / 100"
            color="primary"
            track-color="grey-4"
            rounded
            size="12px"
            class="q-mb-md"
          />

          <div class="row items-center justify-between">
            <div class="text-caption">
              <q-icon name="check_circle" color="positive" />
              {{ completedTasks }} completed
            </div>

            <div class="text-caption"> {{ remainingTasks }} remaining </div>
          </div>
        </q-card-section>
      </q-card>

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

          <q-select
            v-model="categoryFilter"
            outlined
            dense
            emit-value
            map-options
            :options="categoryOptions"
            label="Category"
            class="filter-select"
            clearable
          />

          <q-select
            v-model="sortBy"
            outlined
            dense
            emit-value
            map-options
            :options="sortOptions"
            label="Sort By"
            class="filter-select"
          />

          <q-btn
            v-if="showOnlyOverdue"
            flat
            dense
            no-caps
            color="negative"
            icon="close"
            label="Clear overdue"
            @click="showOnlyOverdue = false"
          />
        </q-card-section>
      </q-card>

      <!-- Loading -->
      <div v-if="taskStore.loading" class="loading-container">
        <q-spinner color="primary" size="40px" />
        <div>Loading tasks...</div>
      </div>

      <!-- Error -->
      <q-banner v-else-if="taskStore.error" rounded class="error-banner">
        <template #avatar>
          <q-icon name="error_outline" />
        </template>

        {{ taskStore.error }}

        <template #action>
          <q-btn flat label="Try Again" @click="loadTasks" />
        </template>
      </q-banner>

      <!-- Empty -->
      <div v-else-if="filteredTasks.length === 0" class="empty-state">
        <q-icon name="task_alt" size="70px" color="grey-5" />

        <h2>No tasks found</h2>

        <p>
          {{
            search || statusFilter || priorityFilter || categoryFilter
              ? "Try changing your filters."
              : "Create your first task to get started."
          }}
        </p>

        <q-btn
          v-if="!search && !statusFilter && !priorityFilter && !categoryFilter"
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
          <q-card-section class="q-py-sm">
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

                  <q-badge
                    :color="urgencyColor(task)"
                    :label="getTaskUrgency(task)"
                  />

                  <q-badge
                    :style="{
                      backgroundColor: categoryColor(task.category || 'Other'),
                      color: '#29243A',
                      fontWeight: '700'
                    }"
                    :label="task.category || 'Other'"
                  />
                  <q-badge
                    v-if="task.is_recurring"
                    color="purple-2"
                    text-color="deep-purple-9"
                    label="↻ Repeats"
                  />

                  <q-badge
                    v-else-if="task.recurrence_parent_id"
                    color="pink-2"
                    text-color="pink-10"
                    label="↻ Repeated"
                  />
                </div>

                <p v-if="task.description" class="task-description">
                  {{ task.description }}
                </p>

                <div class="task-meta">
                  <span
                    v-if="task.due_date"
                    class="meta-item"
                    :class="{
                      'overdue-text':
                        task.status !== 'completed' &&
                        isTaskOverdue(task.due_date)
                    }"
                  >
                    <q-icon
                      :name="
                        task.status !== 'completed' &&
                        isTaskOverdue(task.due_date)
                          ? 'warning'
                          : 'event'
                      "
                      size="16px"
                    />

                    {{ formatDate(task.due_date) }}
                  </span>

                  <span
                    v-if="
                      task.status === 'completed' ||
                      getTaskUrgency(task) !== 'Overdue'
                    "
                    class="meta-item"
                  >
                    <q-icon
                      :name="
                        task.status === 'completed'
                          ? 'check_circle'
                          : 'schedule'
                      "
                      size="16px"
                    />

                    {{ task.status === "completed" ? "Completed" : "Pending" }}
                  </span>
                </div>
              </div>

              <div class="task-actions">
                <q-btn
                  flat
                  round
                  dense
                  icon="visibility"
                  color="primary"
                  @click.stop="openTaskDetails(task)"
                >
                  <q-tooltip>View task details</q-tooltip>
                </q-btn>

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

      <!-- Add/Edit Dialog -->
      <q-dialog v-model="dialogOpen">
        <q-card class="task-dialog">
          <q-card-section>
            <div class="dialog-title">
              {{ editingTask ? "Edit Task" : "Add Task" }}
            </div>

            <div class="dialog-subtitle">
              {{
                editingTask ? "Update your task details." : "Create a new task."
              }}
            </div>
          </q-card-section>

          <q-form @submit="saveTask">
            <q-card-section class="q-pt-none">
              <q-input
                v-model="form.title"
                outlined
                label="Task title *"
                :rules="[value => !!value || 'Task title is required']"
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

              <div class="row q-col-gutter-sm">
                <div class="col-12 col-sm-7">
                  <q-input
                    v-model="form.due_date"
                    outlined
                    type="date"
                    label="Due date"
                    class="form-field"
                  />
                </div>

                <div class="col-12 col-sm-5">
                  <q-input
                    v-model="form.due_time"
                    outlined
                    type="time"
                    label="Due time"
                    class="form-field"
                  />
                </div>
              </div>

              <q-banner
                v-if="isOverdueDate"
                rounded
                class="overdue-warning q-mb-md"
              >
                <template #avatar>
                  <q-icon name="warning" color="negative" />
                </template>

                <div class="text-weight-bold"
                  >This task is already overdue!</div
                >
                <div class="text-caption">
                  The selected due date has already passed. You can still save
                  this task, and it will be included in overdue reminders.
                </div>
              </q-banner>

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
                v-model="form.category"
                outlined
                emit-value
                map-options
                :options="categoryFormOptions"
                label="Category"
                class="form-field"
                :rules="[value => !!value || 'Please select a category']"
              />

              <!-- Recurring Task Settings -->
              <div class="full-width q-mt-md">
                <q-toggle
                  v-model="form.is_recurring"
                  label="Repeat this task"
                  color="primary"
                />

                <div v-if="form.is_recurring" class="q-gutter-md q-mt-sm">
                  <q-select
                    v-model="form.recurrence_frequency"
                    label="Repeat frequency"
                    outlined
                    emit-value
                    map-options
                    :options="[
                      { label: 'Daily', value: 'daily' },
                      { label: 'Weekly', value: 'weekly' },
                      { label: 'Monthly', value: 'monthly' },
                      { label: 'Custom (every few days)', value: 'custom' }
                    ]"
                  />

                  <q-input
                    v-model.number="form.recurrence_interval"
                    type="number"
                    label="Repeat every"
                    outlined
                    min="1"
                    max="365"
                    hint="For custom, this number represents days."
                  />

                  <q-input
                    v-model="form.recurrence_end_date"
                    type="date"
                    label="Stop repeating on (optional)"
                    outlined
                  />
                </div>
              </div>

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

              <q-select
                v-model="form.goal_id"
                outlined
                clearable
                emit-value
                map-options
                :options="goalOptions"
                label="Link to Goal (optional)"
                class="form-field"
                hint="Choose a goal to connect this task to."
              />
            </q-card-section>

            <q-card-actions align="right" class="q-pa-md">
              <q-btn flat no-caps label="Cancel" v-close-popup />

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
      <!-- Task Details Dialog -->
      <q-dialog v-model="taskDetailsOpen">
        <q-card class="task-details-dialog">
          <q-card-section class="row items-center justify-between">
            <div class="text-h6">Task Details</div>

            <q-btn flat round dense icon="close" v-close-popup />
          </q-card-section>

          <q-separator />

          <q-card-section v-if="selectedTask">
            <div class="text-h5 text-weight-bold q-mb-sm">
              {{ selectedTask.title }}
            </div>

            <div v-if="selectedTask.description" class="text-body2 q-mb-md">
              {{ selectedTask.description }}
            </div>

            <q-list separator>
              <q-item>
                <q-item-section avatar>
                  <q-icon name="event" color="primary" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Due Date and Time</q-item-label>
                  <q-item-label>
                    {{
                      selectedTask.due_date
                        ? formatTaskDateTime(selectedTask.due_date)
                        : "No due date"
                    }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section avatar>
                  <q-icon name="category" color="primary" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Category</q-item-label>
                  <q-item-label>
                    {{ selectedTask.category || "Other" }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section avatar>
                  <q-icon name="flag" color="primary" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Priority</q-item-label>
                  <q-item-label>
                    {{ capitalize(selectedTask.priority) }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section avatar>
                  <q-icon name="check_circle" color="primary" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Status</q-item-label>
                  <q-item-label>
                    {{
                      selectedTask.status === "completed"
                        ? "Completed"
                        : selectedTask.due_date &&
                            isTaskOverdue(selectedTask.due_date)
                          ? "Overdue"
                          : "Pending"
                    }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <!-- Recurrence Information -->
              <q-separator spaced />

              <q-item v-if="selectedTask.is_recurring">
                <q-item-section avatar>
                  <q-icon name="repeat" color="deep-purple" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Repeat Schedule</q-item-label>
                  <q-item-label>
                    {{
                      selectedTask.recurrence_frequency === "custom"
                        ? `Every ${selectedTask.recurrence_interval} days`
                        : `Every ${selectedTask.recurrence_interval} ${
                            selectedTask.recurrence_frequency === "daily"
                              ? "day(s)"
                              : selectedTask.recurrence_frequency === "weekly"
                                ? "week(s)"
                                : "month(s)"
                          }`
                    }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item
                v-if="
                  selectedTask.is_recurring && selectedTask.next_recurrence_date
                "
              >
                <q-item-section avatar>
                  <q-icon name="event_repeat" color="deep-purple" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Next Occurrence</q-item-label>
                  <q-item-label>
                    {{ formatTaskDateTime(selectedTask.next_recurrence_date) }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item
                v-if="
                  selectedTask.is_recurring && selectedTask.recurrence_end_date
                "
              >
                <q-item-section avatar>
                  <q-icon name="event_busy" color="deep-purple" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Repeat Until</q-item-label>
                  <q-item-label>
                    {{ formatTaskDateTime(selectedTask.recurrence_end_date) }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="selectedTask.recurrence_parent_id">
                <q-item-section avatar>
                  <q-icon name="repeat" color="pink" />
                </q-item-section>

                <q-item-section>
                  <q-item-label caption>Recurring Task</q-item-label>
                  <q-item-label
                    >Automatically generated occurrence</q-item-label
                  >
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <!-- Task Attachments -->
          <q-separator />

          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="attach_file" color="primary" class="q-mr-sm" />
              Attachments
            </div>

            <q-file
              v-model="selectedFile"
              outlined
              dense
              clearable
              accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv"
              label="Choose a file"
              hint="Maximum file size: 10 MB"
              class="q-mb-sm"
            />

            <q-btn
              color="primary"
              unelevated
              no-caps
              icon="cloud_upload"
              label="Upload Attachment"
              :loading="uploadingAttachment"
              :disable="!selectedFile"
              @click="uploadAttachment"
            />

            <q-separator class="q-my-md" />

            <div v-if="attachmentsLoading" class="text-center q-pa-md">
              <q-spinner color="primary" size="30px" />
              <div class="text-caption q-mt-sm">Loading attachments...</div>
            </div>

            <div
              v-else-if="attachments.length === 0"
              class="text-grey text-center q-pa-md"
            >
              No attachments yet.
            </div>

            <q-list v-else separator>
              <template v-for="attachment in attachments" :key="attachment.id">
                <q-item>
                  <q-item-section avatar>
                    <q-icon
                      :name="
                        attachment.mime_type?.startsWith('image/')
                          ? 'image'
                          : 'description'
                      "
                      color="primary"
                      size="sm"
                    />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label>{{ attachment.original_name }}</q-item-label>
                    <q-item-label caption>
                      {{ (attachment.file_size / 1024).toFixed(1) }} KB
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <div class="row items-center">
                      <a
                        :href="getAttachmentUrl(attachment)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="attachment-action"
                        title="Open attachment"
                      >
                        <q-icon
                          name="open_in_new"
                          color="primary"
                          size="20px"
                        />
                      </a>

                      <button
                        type="button"
                        class="attachment-action"
                        title="Delete attachment"
                        @click="deleteAttachment(attachment)"
                      >
                        <q-icon name="delete" color="negative" size="20px" />
                      </button>
                    </div>
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat label="Close" color="primary" v-close-popup />

            <q-btn
              unelevated
              label="Edit Task"
              color="primary"
              @click="
                taskDetailsOpen = false;
                selectedTask && openEditDialog(selectedTask);
              "
            />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useQuasar } from "quasar";
import { useTaskStore } from "../stores/task-store";
import type { Task } from "../stores/task-store";
import {
  attachmentService,
  type TaskAttachment
} from "../services/attachmentService";
import { api } from "../boot/axios";

const $q = useQuasar();
const taskStore = useTaskStore();

interface GoalOption {
  label: string;
  value: number;
}

const goalOptions = ref<GoalOption[]>([]);
const selectedGoalId = ref<number | null>(null);

async function loadGoalOptions() {
  try {
    const response = await api.get("/goals");

    goalOptions.value = response.data.map(
      (goal: { id: number; title: string }) => ({
        label: goal.title,
        value: goal.id
      })
    );
  } catch (error) {
    console.error("Failed to load goals:", error);
    $q.notify({
      type: "negative",
      message: "Unable to load goals."
    });
  }
}

const selectedTask = ref<Task | null>(null);
const taskDetailsOpen = ref(false);

const attachments = ref<TaskAttachment[]>([]);
const attachmentsLoading = ref(false);
const uploadingAttachment = ref(false);
const selectedFile = ref<File | null>(null);

function getAttachmentUrl(attachment: TaskAttachment): string {
  const path = attachment.cloudinary_url || attachment.file_path;

  if (path.startsWith("http://") || path.startsWith("https://")) {
    return path;
  }

  return `/storage/${path}`;
}

// Filters
const search = ref("");
const statusFilter = ref("");
const priorityFilter = ref("");
const categoryFilter = ref("");
const showOnlyOverdue = ref(false);

// Dialog state
const dialogOpen = ref(false);
const saving = ref(false);
const editingTask = ref<Task | null>(null);

// Task form
const form = reactive({
  title: "",
  description: "",
  due_date: "",
  due_time: "17:00",
  priority: "medium" as "low" | "medium" | "high",
  category: "Other" as "School" | "Work" | "Personal" | "Health" | "Other",
  goal_id: null as number | null,
  status: "pending" as "pending" | "completed",

  is_recurring: false,
  recurrence_frequency: "daily" as "daily" | "weekly" | "monthly" | "custom",
  recurrence_interval: 1,
  recurrence_end_date: ""
});

// Date warning for the task form
const isOverdueDate = computed(() => {
  if (!form.due_date) return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueDate = new Date(`${form.due_date.slice(0, 10)}T00:00:00`);

  return dueDate < today;
});

// Filter options
const statusOptions = [
  { label: "All Statuses", value: "" },
  { label: "Pending", value: "pending" },
  { label: "Completed", value: "completed" },
  { label: "Overdue", value: "overdue" }
];

const priorityOptions = [
  { label: "All Priorities", value: "" },
  { label: "Low", value: "low" },
  { label: "Medium", value: "medium" },
  { label: "High", value: "high" }
];

const sortBy = ref("due_earliest");

const sortOptions = [
  { label: "Due Date (Earliest First)", value: "due_earliest" },
  { label: "Due Date (Latest First)", value: "due_latest" },
  { label: "Priority (High to Low)", value: "priority_high" },
  { label: "Priority (Low to High)", value: "priority_low" },
  { label: "Recently Added", value: "newest" },
  { label: "Oldest First", value: "oldest" }
];

const priorityFormOptions = [
  { label: "Low", value: "low" },
  { label: "Medium", value: "medium" },
  { label: "High", value: "high" }
];
const categoryOptions = [
  { label: "All Categories", value: "" },
  { label: "School", value: "School" },
  { label: "Work", value: "Work" },
  { label: "Personal", value: "Personal" },
  { label: "Health", value: "Health" },
  { label: "Other", value: "Other" }
];

const categoryFormOptions = categoryOptions.filter(
  option => option.value !== ""
);

const statusFormOptions = [
  { label: "Pending", value: "pending" },
  { label: "Completed", value: "completed" }
];

// Check whether a task's exact deadline has passed
function isTaskOverdue(date: string | null): boolean {
  if (!date) return false;

  const dueDate = new Date(date);

  if (Number.isNaN(dueDate.getTime())) {
    return false;
  }

  return dueDate.getTime() < Date.now();
}

// Determine task urgency using the exact deadline
function getTaskUrgency(task: Task): string {
  if (task.status === "completed") {
    return "Completed";
  }

  if (!task.due_date) {
    return "No Due Date";
  }

  const dueDate = new Date(task.due_date);
  const now = new Date();

  if (Number.isNaN(dueDate.getTime())) {
    return "No Due Date";
  }

  if (dueDate.getTime() < now.getTime()) {
    return "Overdue";
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const dueDay = new Date(dueDate);
  dueDay.setHours(0, 0, 0, 0);

  if (dueDay.getTime() === today.getTime()) {
    return "Due Today";
  }

  if (dueDay.getTime() === tomorrow.getTime()) {
    return "Due Tomorrow";
  }

  return "Upcoming";
}

function urgencyColor(task: Task) {
  const urgency = getTaskUrgency(task);

  switch (urgency) {
    case "Overdue":
      return "negative";
    case "Due Today":
      return "orange";
    case "Due Tomorrow":
      return "warning";
    case "Upcoming":
      return "primary";
    case "Completed":
      return "positive";
    default:
      return "grey";
  }
}

const totalTasks = computed(() => taskStore.tasks.length);

const completedTasks = computed(() => taskStore.completedTasks);

const remainingTasks = computed(() => {
  return totalTasks.value - completedTasks.value;
});

const completionPercentage = computed(() => {
  if (totalTasks.value === 0) return 0;

  return Math.round((completedTasks.value / totalTasks.value) * 100);
});

// Overdue task counter
const overdueTasks = computed(() => {
  return taskStore.tasks.filter(task => {
    return task.status !== "completed" && isTaskOverdue(task.due_date);
  }).length;
});

// Show only overdue tasks
function showOverdueTasks() {
  showOnlyOverdue.value = false;
  statusFilter.value = "overdue";
  search.value = "";
  priorityFilter.value = "";
}

async function openTaskDetails(task: Task) {
  selectedTask.value = task;
  taskDetailsOpen.value = true;
  attachments.value = [];
  await loadAttachments(task.id);
}
async function loadAttachments(taskId: number) {
  attachmentsLoading.value = true;

  try {
    attachments.value = await attachmentService.getAttachments(taskId);
  } catch (error) {
    console.error("Failed to load attachments:", error);
    $q.notify({
      type: "negative",
      message: "Unable to load task attachments."
    });
  } finally {
    attachmentsLoading.value = false;
  }
}

async function uploadAttachment() {
  if (!selectedTask.value || !selectedFile.value) return;

  uploadingAttachment.value = true;

  try {
    await attachmentService.uploadAttachment(
      selectedTask.value.id,
      selectedFile.value
    );

    selectedFile.value = null;
    await loadAttachments(selectedTask.value.id);

    $q.notify({
      type: "positive",
      message: "Attachment uploaded successfully!"
    });
  } catch (error) {
    console.error("Failed to upload attachment:", error);
    $q.notify({
      type: "negative",
      message: "Unable to upload attachment."
    });
  } finally {
    uploadingAttachment.value = false;
  }
}

async function deleteAttachment(attachment: TaskAttachment) {
  try {
    await attachmentService.deleteAttachment(attachment.id);

    attachments.value = attachments.value.filter(
      item => item.id !== attachment.id
    );

    $q.notify({
      type: "positive",
      message: "Attachment deleted successfully!"
    });
  } catch (error) {
    console.error("Failed to delete attachment:", error);
    $q.notify({
      type: "negative",
      message: "Unable to delete attachment."
    });
  }
}
// Task filtering

const filteredTasks = computed(() => {
  const filtered = taskStore.tasks.filter(task => {
    const searchText = search.value.toLowerCase();

    const matchesSearch =
      !searchText ||
      task.title.toLowerCase().includes(searchText) ||
      (task.description ?? "").toLowerCase().includes(searchText);

    const matchesStatus = (() => {
      if (!statusFilter.value) return true;

      if (statusFilter.value === "overdue") {
        return task.status !== "completed" && isTaskOverdue(task.due_date);
      }

      if (statusFilter.value === "pending") {
        return task.status === "pending" && !isTaskOverdue(task.due_date);
      }

      if (statusFilter.value === "completed") {
        return task.status === "completed";
      }

      return true;
    })();

    const matchesPriority =
      !priorityFilter.value || task.priority === priorityFilter.value;

    const matchesCategory =
      !categoryFilter.value || task.category === categoryFilter.value;

    const matchesOverdue =
      !showOnlyOverdue.value ||
      (task.status !== "completed" && isTaskOverdue(task.due_date));

    return (
      matchesSearch &&
      matchesStatus &&
      matchesPriority &&
      matchesCategory &&
      matchesOverdue
    );
  });

  const priorityOrder: Record<string, number> = {
    high: 3,
    medium: 2,
    low: 1
  };

  return [...filtered].sort((a, b) => {
    switch (sortBy.value) {
      case "due_earliest":
        return (a.due_date || "9999-12-31").localeCompare(
          b.due_date || "9999-12-31"
        );

      case "due_latest":
        return (b.due_date || "").localeCompare(a.due_date || "");

      case "priority_high":
        return (
          (priorityOrder[b.priority] ?? 0) - (priorityOrder[a.priority] ?? 0)
        );

      case "priority_low":
        return (
          (priorityOrder[a.priority] ?? 0) - (priorityOrder[b.priority] ?? 0)
        );

      case "newest":
        return b.id - a.id;

      case "oldest":
        return a.id - b.id;

      default:
        return 0;
    }
  });
});

// Load tasks
async function loadTasks() {
  await taskStore.fetchTasks();
}

// Reset form
function resetForm() {
  form.title = "";
  form.description = "";
  form.due_date = "";
  form.due_time = "17:00";
  form.priority = "medium";
  form.category = "Other";
  form.goal_id = null;
  form.status = "pending";
  form.is_recurring = false;
  form.recurrence_frequency = "daily";
  form.recurrence_interval = 1;
  form.recurrence_end_date = "";
}

// Open create dialog
function openCreateDialog() {
  editingTask.value = null;
  resetForm();
  dialogOpen.value = true;
}

// Open edit dialog
function openEditDialog(task: Task) {
  editingTask.value = task;

  form.title = task.title;
  form.description = task.description ?? "";
  form.due_date = task.due_date
    ? new Date(task.due_date).toLocaleDateString("en-CA")
    : "";

  form.due_time = task.due_date
    ? new Date(task.due_date).toLocaleTimeString("en-GB", {
        hour: "2-digit",
        minute: "2-digit"
      })
    : "17:00";

  form.priority = task.priority;
  form.category = task.category ?? "Other";
  form.goal_id = task.goal_id ?? null;
  form.status = task.status;

  // Restore recurring task settings when editing
  form.is_recurring = task.is_recurring ?? false;
  form.recurrence_frequency = task.recurrence_frequency ?? "daily";
  form.recurrence_interval = task.recurrence_interval ?? 1;
  form.recurrence_end_date = task.recurrence_end_date
    ? new Date(task.recurrence_end_date).toLocaleDateString("en-CA")
    : "";

  dialogOpen.value = true;
}

// Save or update task

async function saveTask() {
  saving.value = true;

  try {
    const wasEditing = !!editingTask.value;

    const dueDateTime = form.due_date
      ? `${form.due_date} ${form.due_time || "17:00"}:00`
      : "";

    const recurrenceEndDate = form.recurrence_end_date
      ? `${form.recurrence_end_date} 23:59:59`
      : null;

    const taskData = {
      title: form.title,
      description: form.description,
      due_date: dueDateTime,
      priority: form.priority,
      category: form.category,
      goal_id: form.goal_id,
      status: form.status,

      is_recurring: form.is_recurring,
      recurrence_frequency: form.is_recurring
        ? form.recurrence_frequency
        : null,
      recurrence_interval: form.recurrence_interval,
      recurrence_end_date: form.is_recurring ? recurrenceEndDate : null
    };

    if (editingTask.value) {
      await taskStore.updateTask(editingTask.value.id, taskData);
    } else {
      await taskStore.addTask(taskData);
    }

    dialogOpen.value = false;
    editingTask.value = null;

    $q.notify({
      type: "positive",
      message: wasEditing
        ? "Task updated successfully"
        : "Task created successfully"
    });

    await loadTasks();
  } catch (error) {
    console.error("Save task error:", error);

    $q.notify({
      type: "negative",
      message: "Failed to save task"
    });
  } finally {
    saving.value = false;
  }
}

// Toggle task completion
async function toggleTask(task: Task) {
  try {
    await taskStore.toggleTask(task.id);
  } catch (error) {
    console.error(error);

    $q.notify({
      type: "negative",
      message: "Unable to update task."
    });
  }
}

// Delete task
async function confirmDelete(task: Task) {
  const confirmed = window.confirm(
    `Are you sure you want to delete "${task.title}"?`
  );

  if (!confirmed) return;

  try {
    await taskStore.deleteTask(task.id);

    $q.notify({
      type: "positive",
      message: "Task deleted successfully."
    });
  } catch (error) {
    console.error("Delete error:", error);

    $q.notify({
      type: "negative",
      message: "Unable to delete task."
    });
  }
}

// Priority badge colors
function priorityColor(priority: Task["priority"]) {
  if (priority === "high") return "negative";
  if (priority === "medium") return "warning";
  return "positive";
}
// Category badge colors
function categoryColor(category: Task["category"]) {
  const colors: Record<Task["category"], string> = {
    School: "#A78BFA",
    Work: "#60A5FA",
    Personal: "#F472B6",
    Health: "#34D399",
    Other: "#FBBF24"
  };

  return colors[category] ?? colors.Other;
}

// Capitalize text
function capitalize(value: string) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

// Format due date
function formatDate(date: string | null) {
  if (!date) return "";

  const normalizedDate = /^\d{4}-\d{2}-\d{2}$/.test(date)
    ? `${date}T00:00:00`
    : date;

  const parsedDate = new Date(normalizedDate);

  if (Number.isNaN(parsedDate.getTime())) {
    return "Invalid Date";
  }

  return parsedDate.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric"
  });
}

function formatTaskDateTime(date: string): string {
  const parsedDate = new Date(date);

  if (Number.isNaN(parsedDate.getTime())) {
    return "Invalid Date";
  }

  return parsedDate.toLocaleString("en-US", {
    month: "long",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true
  });
}

// Initial loading
onMounted(() => {
  void loadTasks();
  void loadGoalOptions();
});
</script>

<style lang="scss" scoped>
.task-details-dialog {
  width: 100%;
  max-width: 450px;
  border-radius: 16px;
}

.task-page {
  background: #f8f6ff;
  min-height: 100vh;
}
.progress-card {
  border-radius: 14px;
  background: rgba(155, 135, 213, 0.08);
}

.progress-percentage {
  font-size: 26px;
  font-weight: 800;
  color: var(--q-primary);
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
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 18px;
}

.stat-card {
  border-radius: 14px;
  background: #ffffff;
  border: 1px solid #eee8fa;
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

.task-card:hover {
  transform: translateY(-1px);
}

.task-card.completed {
  opacity: 0.7;
}

.task-card.completed h3 {
  text-decoration: line-through;
}

.task-list {
  display: grid;
  gap: 10px;
}

.task-card {
  border-radius: 14px;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 16px rgba(155, 135, 213, 0.12);
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
  gap: 8px;
}

.task-information {
  flex: 1;
  min-width: 0;
}

.task-title-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 7px;
}

.task-title-row h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 750;
  line-height: 1.4;
}

.task-description {
  color: #aaa0bd;
  margin: 5px 0 7px;
  font-size: 13px;
  line-height: 1.5;
  white-space: pre-wrap;
}

.task-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  font-size: 12px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.task-actions {
  display: flex;
  gap: 2px;
  align-items: center;
}

.loading-container,
.empty-state {
  text-align: center;
  padding: 55px 20px;
}

.empty-state {
  background: #ffffff;
  border: 1px dashed #d9cbed;
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
    grid-template-columns: repeat(2, 1fr);
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

/* ========================================
   SOFT LAVENDER DARK MODE
   ======================================== */

/* Main page */
:global(body.body--dark .task-page) {
  background: #1c1930 !important;
  color: #f4efff !important;
}

/* Header */
:global(body.body--dark .task-page .page-header h1) {
  color: #f4efff !important;
}

:global(body.body--dark .task-page .page-header p) {
  color: #c0b6d8 !important;
}

:global(body.body--dark .task-page .eyebrow) {
  color: #c4a8ff !important;
}

/* Statistics cards - target actual Quasar QCards */
:global(body.body--dark .task-page .q-card.stat-card) {
  background: #302944 !important;
  color: #f4efff !important;
  border: 1px solid #514267 !important;
}

:global(body.body--dark .task-page .stat-label) {
  color: #c5b8df !important;
}

:global(body.body--dark .task-page .stat-number) {
  color: #f4efff !important;
}

/* Search and filter card */
:global(body.body--dark .task-page .q-card.filter-card) {
  background: #302944 !important;
  color: #f4efff !important;
  border: 1px solid #514267 !important;
}

/* Search and dropdown fields */
:global(body.body--dark .task-page .q-field__control) {
  background: #211c30 !important;
  color: #f4efff !important;
}

:global(body.body--dark .task-page .q-field__native),
:global(body.body--dark .task-page .q-field__input),
:global(body.body--dark .task-page .q-field__label),
:global(body.body--dark .task-page .q-field__marginal) {
  color: #eee6ff !important;
}

:global(
  body.body--dark .task-page .q-field--outlined .q-field__control:before
) {
  border-color: #66567f !important;
}

:global(
  body.body--dark .task-page .q-field--outlined .q-field__control:hover:before
) {
  border-color: #b7a0e8 !important;
}

/* Task cards */
:global(body.body--dark .task-page .q-card.task-card) {
  background: #302944 !important;
  color: #f4efff !important;
  border: 1px solid #514267 !important;
}

:global(body.body--dark .task-page .task-title-row h3) {
  color: #f4efff !important;
}

:global(body.body--dark .task-page .task-description) {
  color: #c5b8df !important;
}

:global(body.body--dark .task-page .task-meta) {
  color: #b9a9d5 !important;
}

/* Empty state */
:global(body.body--dark .task-page .empty-state) {
  background: #302944 !important;
  border: 1px dashed #66567f !important;
  color: #f4efff !important;
}

:global(body.body--dark .task-page .empty-state h2) {
  color: #f4efff !important;
}

:global(body.body--dark .task-page .empty-state p) {
  color: #c5b8df !important;
}

/* Loading */
:global(body.body--dark .task-page .loading-container) {
  color: #f4efff !important;
}

/* Add/Edit dialog */
:global(body.body--dark .task-dialog) {
  background: #302944 !important;
  color: #f4efff !important;
}

:global(body.body--dark .task-dialog .dialog-title) {
  color: #f4efff !important;
}

:global(body.body--dark .task-dialog .dialog-subtitle) {
  color: #c5b8df !important;
}

:global(body.body--dark .task-dialog .q-field__control) {
  background: #211c30 !important;
}

:global(body.body--dark .task-dialog .q-field__native),
:global(body.body--dark .task-dialog .q-field__input),
:global(body.body--dark .task-dialog .q-field__label) {
  color: #eee6ff !important;
}

/* Error banner */
:global(body.body--dark .task-page .error-banner) {
  background: #49283d !important;
  color: #ffdce9 !important;
}

.overdue-warning {
  background: #fff1f2;
  color: #991b1b;
  border: 1px solid #fecdd3;
}

.overdue-text {
  color: #dc2626 !important;
  font-weight: 700;
}

:global(body.body--dark .overdue-warning) {
  background: #49283d;
  color: #ffdce9;
  border-color: #82445f;
}

:global(body.body--dark .overdue-text) {
  color: #ff8da8 !important;
}
</style>
