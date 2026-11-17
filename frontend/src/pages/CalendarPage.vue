<template>
  <q-page class="q-pa-lg calendar-page">
    <!-- Page Header -->
    <div class="row items-center justify-between q-mb-lg">
      <div class="row items-center q-gutter-md">
        <q-btn
          flat
          round
          icon="arrow_back"
          color="primary"
          aria-label="Back to dashboard"
          @click="$router.push('/')"
        >
          <q-tooltip>Back to My Tasks</q-tooltip>
        </q-btn>

        <div class="text-h4 text-weight-bold">Task Calendar</div>
        <div class="text-grey-6"> View and organize your tasks by date. </div>
      </div>

      <q-btn
        color="primary"
        label="Today"
        icon="today"
        unelevated
        @click="goToToday"
      />
    </div>

    <!-- Monthly Calendar -->
    <q-card flat bordered class="calendar-card">
      <q-card-section class="row items-center justify-between">
        <div class="text-h5 text-weight-bold">
          {{ monthLabel }}
        </div>

        <div class="row q-gutter-sm">
          <q-btn round flat icon="chevron_left" @click="changeMonth(-1)" />
          <q-btn round flat icon="chevron_right" @click="changeMonth(1)" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="calendar-grid">
          <div v-for="day in weekDays" :key="day" class="weekday">
            {{ day }}
          </div>

          <button
            v-for="cell in calendarDays"
            :key="cell.date"
            class="calendar-day"
            :class="{
              'other-month': !cell.currentMonth,
              'selected-day': selectedDate === cell.date,
              'today-day': cell.today
            }"
            @click="selectedDate = cell.date"
          >
            <span class="day-number">{{ cell.day }}</span>

            <div class="day-indicators">
              <span
                v-for="task in tasksForDate(cell.date).slice(0, 3)"
                :key="task.id"
                class="task-dot"
                :style="{ backgroundColor: categoryColor(task.category) }"
                :class="{ 'completed-dot': task.status === 'completed' }"
              />
            </div>

            <span v-if="tasksForDate(cell.date).length > 3" class="more-tasks">
              +{{ tasksForDate(cell.date).length - 3 }}
            </span>
          </button>
        </div>
      </q-card-section>

      <!-- Category Legend -->
      <q-card-section class="calendar-legend">
        <div v-for="category in categories" :key="category" class="legend-item">
          <span
            class="legend-dot"
            :style="{ backgroundColor: categoryColor(category) }"
          />
          {{ category }}
        </div>
      </q-card-section>
    </q-card>

    <!-- Tasks for Selected Date -->
    <q-card flat bordered class="q-mt-lg selected-date-card">
      <q-card-section>
        <div class="text-h6 text-weight-bold">
          Tasks for {{ selectedDateLabel }}
        </div>

        <div v-if="selectedTasks.length === 0" class="text-grey-6 q-mt-md">
          No tasks scheduled for this date.
        </div>

        <div v-else class="q-mt-md">
          <q-card
            v-for="task in selectedTasks"
            :key="task.id"
            flat
            bordered
            class="task-item q-mb-sm"
          >
            <q-card-section class="row items-center justify-between">
              <div class="col">
                <div class="text-weight-bold">
                  {{ task.title }}
                </div>

                <div class="text-caption text-grey-6">
                  {{ task.description || "No description" }}
                </div>

                <div class="row items-center q-gutter-sm q-mt-sm">
                  <q-badge
                    :style="{
                      backgroundColor: categoryColor(task.category),
                      color: '#29243A'
                    }"
                    :label="task.category"
                  />

                  <q-badge
                    :color="getDisplayStatusColor(task)"
                    :label="getDisplayStatus(task)"
                    class="text-capitalize"
                  />

                  <q-badge
                    color="grey-7"
                    :label="task.priority + ' priority'"
                  />
                </div>
              </div>

              <!-- View Details Button -->
              <q-btn
                unelevated
                rounded
                color="primary"
                icon="visibility"
                label="View Details"
                size="sm"
                @click="openTaskDetails(task)"
              />
            </q-card-section>
          </q-card>
        </div>
      </q-card-section>
    </q-card>

    <!-- Task Details Dialog -->
    <q-dialog v-model="showTaskDetails">
      <q-card class="task-details-card">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold"> Task Details </div>

          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section v-if="selectedTask">
          <div class="text-h5 text-weight-bold q-mb-sm">
            {{ selectedTask.title }}
          </div>

          <div class="text-grey-6 q-mb-lg">
            {{ selectedTask.description || "No description provided." }}
          </div>

          <q-list separator>
            <q-item>
              <q-item-section avatar>
                <q-icon name="event" color="primary" />
              </q-item-section>

              <q-item-section>
                <q-item-label caption>Due Date</q-item-label>
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
                  <q-badge
                    :style="{
                      backgroundColor: categoryColor(selectedTask.category),
                      color: '#29243A'
                    }"
                    :label="selectedTask.category"
                  />
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section avatar>
                <q-icon name="flag" color="primary" />
              </q-item-section>

              <q-item-section>
                <q-item-label caption>Priority</q-item-label>
                <q-item-label class="text-capitalize">
                  {{ selectedTask.priority }}
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
                  <q-badge
                    :color="getDisplayStatusColor(selectedTask)"
                    :label="getDisplayStatus(selectedTask)"
                    class="text-capitalize"
                  />
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>

          <!-- Task Attachments -->
          <q-separator class="q-my-md" />

          <div class="text-subtitle1 text-weight-bold q-mb-md">
            <q-icon name="attach_file" color="primary" class="q-mr-sm" />
            Attachments
          </div>

          <div v-if="attachmentsLoading" class="text-center q-pa-md">
            <q-spinner color="primary" size="30px" />
            <div class="text-caption q-mt-sm"> Loading attachments... </div>
          </div>

          <div
            v-else-if="attachments.length === 0"
            class="text-grey text-center q-pa-md"
          >
            No attachments yet.
          </div>

          <div v-else class="column q-gutter-md">
            <q-card
              v-for="file in attachments"
              :key="file.id"
              flat
              bordered
              class="q-pa-sm"
            >
              <a
                :href="getAttachmentUrl(file)"
                target="_blank"
                rel="noopener noreferrer"
                style="text-decoration: none; color: inherit"
              >
                <img
                  v-if="isImageAttachment(file)"
                  :src="getAttachmentUrl(file)"
                  :alt="file.original_name"
                  style="width: 100%; height: 180px; object-fit: contain"
                />

                <div class="row items-center q-gutter-sm q-mt-sm">
                  <q-icon name="attachment" color="primary" size="sm" />
                  <span>Open attachment</span>
                </div>
              </a>
            </q-card>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat color="primary" label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useTaskStore, type Task } from "../stores/task-store";
import {
  attachmentService,
  type TaskAttachment
} from "../services/attachmentService";

const taskStore = useTaskStore();

const weekDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const categories: Task["category"][] = [
  "School",
  "Work",
  "Personal",
  "Health",
  "Other"
];

const currentMonth = ref(new Date());
const selectedDate = ref(formatDate(new Date()));

// Task details dialog
const showTaskDetails = ref(false);
const selectedTask = ref<Task | null>(null);

const attachments = ref<TaskAttachment[]>([]);
const attachmentsLoading = ref(false);

function isImageAttachment(attachment: TaskAttachment): boolean {
  return attachment.mime_type?.startsWith("image/") ?? false;
}

function getAttachmentUrl(attachment: TaskAttachment): string {
  const path = attachment.cloudinary_url || attachment.file_path;

  if (path.startsWith("http://") || path.startsWith("https://")) {
    return path;
  }

  return `/storage/${path}`;
}

function formatDate(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

const monthLabel = computed(() =>
  currentMonth.value.toLocaleDateString("en-US", {
    month: "long",
    year: "numeric"
  })
);

const selectedDateLabel = computed(() => {
  const [year, month, day] = selectedDate.value.split("-").map(Number);

  if (!year || !month || !day) {
    return "Invalid Date";
  }

  return new Date(year, month - 1, day).toLocaleDateString("en-US", {
    month: "long",
    day: "numeric",
    year: "numeric"
  });
});

// Generate calendar days
const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear();
  const month = currentMonth.value.getMonth();

  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  const cells: {
    day: number;
    date: string;
    currentMonth: boolean;
    today: boolean;
  }[] = [];

  for (let i = 0; i < firstDay; i++) {
    const date = new Date(year, month, i - firstDay + 1);

    cells.push({
      day: date.getDate(),
      date: formatDate(date),
      currentMonth: false,
      today: formatDate(date) === formatDate(new Date())
    });
  }

  for (let day = 1; day <= daysInMonth; day++) {
    const date = new Date(year, month, day);

    cells.push({
      day,
      date: formatDate(date),
      currentMonth: true,
      today: formatDate(date) === formatDate(new Date())
    });
  }

  const remaining = 42 - cells.length;

  for (let i = 1; i <= remaining; i++) {
    const date = new Date(year, month + 1, i);

    cells.push({
      day: date.getDate(),
      date: formatDate(date),
      currentMonth: false,
      today: formatDate(date) === formatDate(new Date())
    });
  }

  return cells;
});

// Get tasks for a date
function tasksForDate(date: string): Task[] {
  return taskStore.tasks.filter(task => task.due_date?.slice(0, 10) === date);
}

const selectedTasks = computed(() => tasksForDate(selectedDate.value));

// Navigate between months
function changeMonth(amount: number) {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() + amount,
    1
  );
}

function getDisplayStatus(task: Task): string {
  if (task.status === "completed") {
    return "completed";
  }

  if (task.due_date) {
    const dueDate = new Date(task.due_date);

    if (!Number.isNaN(dueDate.getTime()) && dueDate < new Date()) {
      return "overdue";
    }
  }

  return "pending";
}

function getDisplayStatusColor(task: Task): string {
  const status = getDisplayStatus(task);

  if (status === "completed") return "positive";
  if (status === "overdue") return "negative";

  return "warning";
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

// Return to today
function goToToday() {
  const today = new Date();

  currentMonth.value = today;
  selectedDate.value = formatDate(today);
}

// Category colors
function categoryColor(category: Task["category"]): string {
  const colors: Record<Task["category"], string> = {
    School: "#A78BFA",
    Work: "#60A5FA",
    Personal: "#F472B6",
    Health: "#34D399",
    Other: "#FBBF24"
  };

  return colors[category] ?? colors.Other;
}

// Load task attachments
async function loadAttachments(taskId: number) {
  attachmentsLoading.value = true;

  try {
    attachments.value = await attachmentService.getAttachments(taskId);
  } catch (error) {
    console.error("Failed to load task attachments:", error);
    attachments.value = [];
  } finally {
    attachmentsLoading.value = false;
  }
}

// Open task details
async function openTaskDetails(task: Task) {
  selectedTask.value = task;
  console.log("Selected task ID:", task.id);

  attachments.value = [];
  showTaskDetails.value = true;

  await loadAttachments(task.id);
}

// Load existing Laravel tasks
onMounted(() => {
  void taskStore.fetchTasks();
});
</script>

<style scoped>
.calendar-card,
.selected-date-card {
  border-radius: 16px;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 6px;
}

.weekday {
  text-align: center;
  font-weight: 700;
  color: #9b87d5;
  padding: 10px 0;
}

.calendar-day {
  min-height: 76px;
  border: 1px solid rgba(155, 135, 213, 0.2);
  border-radius: 10px;
  background: transparent;
  color: inherit;
  cursor: pointer;
  padding: 8px 5px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.calendar-day:hover {
  background: rgba(155, 135, 213, 0.12);
}

.day-number {
  font-weight: 600;
}

.other-month {
  opacity: 0.35;
}

.selected-day {
  border: 2px solid #9b87d5;
  background: rgba(155, 135, 213, 0.15);
}

.today-day .day-number {
  color: #9b87d5;
  font-weight: 900;
}

.day-indicators {
  display: flex;
  justify-content: center;
  gap: 4px;
}

.task-dot,
.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.completed-dot {
  opacity: 0.45;
}

.more-tasks {
  font-size: 10px;
  color: #9b87d5;
}

.calendar-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.task-item {
  border-radius: 12px;
}

.task-details-card {
  width: 100%;
  max-width: 450px;
  border-radius: 18px;
}

@media (max-width: 600px) {
  .calendar-day {
    min-height: 58px;
    padding: 5px 2px;
    font-size: 12px;
  }

  .calendar-grid {
    gap: 3px;
  }

  .calendar-legend {
    gap: 10px;
  }
}
</style>
