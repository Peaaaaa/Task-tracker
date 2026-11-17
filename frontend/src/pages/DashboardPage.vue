<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useTaskStore } from "../stores/task-store";
import { Doughnut, Bar } from "vue-chartjs";
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from "chart.js";

ChartJS.register(
  ArcElement,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
);

const taskStore = useTaskStore();
const $q = useQuasar();

const categories = ["School", "Work", "Personal", "Health", "Other"];

function isOverdue(task: (typeof taskStore.tasks)[number]): boolean {
  if (task.status === "completed" || !task.due_date) return false;

  const dueDate = new Date(task.due_date);

  return !Number.isNaN(dueDate.getTime()) && dueDate < new Date();
}

const totalTasks = computed(() => taskStore.tasks.length);

const completedTasks = computed(
  () => taskStore.tasks.filter(task => task.status === "completed").length
);

const pendingTasks = computed(
  () => taskStore.tasks.filter(task => task.status === "pending").length
);

const overdueTasks = computed(() => taskStore.tasks.filter(isOverdue).length);

const completionRate = computed(() =>
  totalTasks.value
    ? Math.round((completedTasks.value / totalTasks.value) * 100)
    : 0
);

const categoryCounts = computed(() =>
  categories.map(category => {
    const tasks = taskStore.tasks.filter(task => task.category === category);
    const completed = tasks.filter(task => task.status === "completed").length;

    return {
      name: category,
      total: tasks.length,
      completed,
      pending: tasks.length - completed,
      percentage: tasks.length
        ? Math.round((completed / tasks.length) * 100)
        : 0
    };
  })
);

const categoryColors = ["#A78BFA", "#F4A6C1", "#83C5BE", "#F6C779", "#A5B4FC"];

const doughnutData = computed(() => ({
  labels: categories,
  datasets: [
    {
      data: categoryCounts.value.map(item => item.total),
      backgroundColor: categoryColors,
      borderWidth: 0,
      hoverOffset: 8
    }
  ]
}));

const barData = computed(() => ({
  labels: categories,
  datasets: [
    {
      label: "Completed",
      data: categoryCounts.value.map(item => item.completed),
      backgroundColor: "#A78BFA",
      borderRadius: 6
    },
    {
      label: "Pending",
      data: categoryCounts.value.map(item => item.pending),
      backgroundColor: "#F4A6C1",
      borderRadius: 6
    }
  ]
}));

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "bottom" as const
    }
  }
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "bottom" as const
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0
      }
    }
  }
};

onMounted(async () => {
  await taskStore.fetchTasks();
});
</script>

<template>
  <q-page
    class="dashboard-page q-pa-md q-pa-lg-lg"
    :class="{ 'dashboard-dark': $q.dark.isActive }"
  >
    <div class="dashboard-container">
      <!-- Header -->
      <div class="row items-center justify-between q-mb-lg">
        <div class="row items-center q-gutter-sm">
          <q-btn
            flat
            round
            dense
            icon="arrow_back"
            aria-label="Back to previous page"
            @click="$router.push('/')"
          >
            <q-tooltip>Go back</q-tooltip>
          </q-btn>

          <div>
            <div class="text-h4 text-weight-bold dashboard-title">
              My Dashboard
            </div>
            <div class="text-subtitle2 text-grey-7 q-mt-xs">
              Here's an overview of your productivity.
            </div>
          </div>
        </div>

        <q-btn
          round
          flat
          color="deep-purple"
          icon="refresh"
          :loading="taskStore.loading"
          @click="taskStore.fetchTasks()"
        >
          <q-tooltip>Refresh dashboard</q-tooltip>
        </q-btn>
      </div>

      <!-- Loading -->
      <div
        v-if="taskStore.loading && !taskStore.tasks.length"
        class="flex flex-center q-pa-xl"
      >
        <q-spinner-dots color="deep-purple" size="50px" />
      </div>

      <template v-else>
        <!-- Error -->
        <q-banner
          v-if="taskStore.error"
          rounded
          class="bg-red-1 text-negative q-mb-md"
        >
          {{ taskStore.error }}
        </q-banner>

        <!-- Summary Cards -->
        <div class="row q-col-gutter-md q-mb-lg">
          <div class="col-12 col-sm-6 col-lg-3">
            <q-card flat bordered class="summary-card total-card">
              <q-card-section>
                <div class="row items-center justify-between">
                  <div class="summary-icon">
                    <q-icon name="checklist" size="24px" />
                  </div>
                  <q-icon name="trending_up" color="deep-purple" size="22px" />
                </div>

                <div class="summary-number">
                  {{ totalTasks }}
                </div>
                <div class="summary-label">Total Tasks</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <q-card flat bordered class="summary-card completed-card">
              <q-card-section>
                <div class="summary-icon">
                  <q-icon name="task_alt" size="24px" />
                </div>
                <div class="summary-number">
                  {{ completedTasks }}
                </div>
                <div class="summary-label">Completed</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <q-card flat bordered class="summary-card pending-card">
              <q-card-section>
                <div class="summary-icon">
                  <q-icon name="schedule" size="24px" />
                </div>
                <div class="summary-number">
                  {{ pendingTasks }}
                </div>
                <div class="summary-label">Pending</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <q-card flat bordered class="summary-card overdue-card">
              <q-card-section>
                <div class="summary-icon">
                  <q-icon name="warning_amber" size="24px" />
                </div>
                <div class="summary-number">
                  {{ overdueTasks }}
                </div>
                <div class="summary-label">Overdue</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Productivity Progress -->
        <q-card flat bordered class="dashboard-card q-mb-lg">
          <q-card-section>
            <div class="row items-center justify-between q-mb-sm">
              <div>
                <div class="text-h6 text-weight-bold">
                  Overall Productivity
                </div>
                <div class="text-caption text-grey-7">
                  Your completed tasks compared to all tasks
                </div>
              </div>

              <div class="text-h4 text-weight-bold text-deep-purple">
                {{ completionRate }}%
              </div>
            </div>

            <q-linear-progress
              :value="completionRate / 100"
              color="deep-purple"
              track-color="deep-purple-1"
              rounded
              size="12px"
            />

            <div class="text-caption text-grey-7 q-mt-sm">
              {{ completedTasks }} out of {{ totalTasks }} tasks completed
            </div>
          </q-card-section>
        </q-card>

        <!-- Charts -->
        <div class="row q-col-gutter-md q-mb-lg">
          <div class="col-12 col-md-5">
            <q-card flat bordered class="dashboard-card full-height">
              <q-card-section>
                <div class="text-h6 text-weight-bold"> Tasks by Category </div>
                <div class="text-caption text-grey-7">
                  Distribution of your tasks
                </div>
              </q-card-section>

              <q-card-section>
                <div class="chart-container">
                  <Doughnut :data="doughnutData" :options="doughnutOptions" />
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-7">
            <q-card flat bordered class="dashboard-card full-height">
              <q-card-section>
                <div class="text-h6 text-weight-bold">
                  Category Performance
                </div>
                <div class="text-caption text-grey-7">
                  Completed versus pending tasks
                </div>
              </q-card-section>

              <q-card-section>
                <div class="chart-container">
                  <Bar :data="barData" :options="barOptions" />
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Category Breakdown -->
        <q-card flat bordered class="dashboard-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold"> Category Breakdown </div>
            <div class="text-caption text-grey-7">
              Track your progress in each category
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section
            v-for="category in categoryCounts"
            :key="category.name"
          >
            <div class="row items-center justify-between q-mb-sm">
              <div class="text-weight-medium">
                {{ category.name }}
              </div>

              <div class="text-caption text-grey-7">
                {{ category.completed }}/{{ category.total }} completed ·
                {{ category.percentage }}%
              </div>
            </div>

            <q-linear-progress
              :value="category.percentage / 100"
              color="deep-purple"
              track-color="grey-3"
              rounded
              size="9px"
            />
          </q-card-section>

          <div v-if="!totalTasks" class="text-center text-grey-6 q-pa-lg">
            <q-icon name="inbox" size="40px" />
            <div class="q-mt-sm">
              No tasks yet. Add your first task to see your statistics!
            </div>
          </div>
        </q-card>
      </template>
    </div>
  </q-page>
</template>

<style scoped>
.dashboard-page {
  background: #faf8ff;
  min-height: 100%;
}

.dashboard-container {
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-title {
  color: #514078;
}

.summary-card,
.dashboard-card {
  border-radius: 16px;
  background: white;
}

.summary-card {
  min-height: 145px;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.summary-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(80, 60, 120, 0.08);
}

.summary-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.summary-number {
  font-size: 30px;
  font-weight: 700;
  color: #514078;
}

.summary-label {
  color: #777080;
  font-size: 14px;
}

.total-card .summary-icon {
  background: #eee8ff;
  color: #805ad5;
}

.completed-card .summary-icon {
  background: #e3f6ed;
  color: #25865a;
}

.pending-card .summary-icon {
  background: #fff0e3;
  color: #c27832;
}

.overdue-card .summary-icon {
  background: #fde8eb;
  color: #c44b59;
}

.chart-container {
  position: relative;
  height: 280px;
  width: 100%;
}

@media (max-width: 600px) {
  .dashboard-title {
    font-size: 25px;
  }

  .chart-container {
    height: 250px;
  }
}

/* Dashboard dark mode */
.dashboard-page.dashboard-dark {
  background: #151321 !important;
  color: #f3efff !important;
}

.dashboard-dark .dashboard-title {
  color: #e4d8ff !important;
}

.dashboard-dark .summary-card,
.dashboard-dark .dashboard-card {
  background: #211d31 !important;
  border-color: #39324d !important;
  color: #f3efff !important;
}

.dashboard-dark .summary-number {
  color: #e4d8ff !important;
}

.dashboard-dark .summary-label,
.dashboard-dark .text-grey-7 {
  color: #c3bad3 !important;
}

.dashboard-dark .q-separator {
  background: #39324d !important;
}
</style>
