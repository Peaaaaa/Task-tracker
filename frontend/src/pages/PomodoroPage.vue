<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { useQuasar } from "quasar";

const $q = useQuasar();
import { Notify } from "quasar";
import { api } from "@/boot/axios";
import type { FocusSession } from "@/services/focusSessionService";
import {
  getFocusSessions,
  saveFocusSession
} from "@/services/focusSessionService";

type TimerMode = "focus" | "break";

const FOCUS_SECONDS = 25 * 60;
const BREAK_SECONDS = 5 * 60;

const mode = ref<TimerMode>("focus");
const remainingSeconds = ref(FOCUS_SECONDS);
const isRunning = ref(false);
const completedSessions = ref(0);
interface TaskOption {
  label: string;
  value: number;
}

const taskOptions = ref<TaskOption[]>([]);
const selectedTaskId = ref<number | null>(null);
const sessionHistory = ref<FocusSession[]>([]);
const isSaving = ref(false);

async function loadTasks() {
  try {
    const response = await api.get("/tasks");
    const tasks = Array.isArray(response.data)
      ? response.data
      : (response.data.data ?? []);

    taskOptions.value = tasks.map((task: { id: number; title: string }) => ({
      label: task.title,
      value: task.id
    }));
  } catch {
    Notify.create({
      type: "negative",
      message: "Unable to load your tasks."
    });
  }
}

async function loadSessionHistory() {
  try {
    const data = await getFocusSessions();

    sessionHistory.value = data.sessions;
    completedSessions.value = data.completed_focus_sessions;
  } catch {
    Notify.create({
      type: "negative",
      message: "Unable to load focus session history."
    });
  }
}

onMounted(() => {
  void loadTasks();
  void loadSessionHistory();
});

let timer: ReturnType<typeof setInterval> | null = null;

const formattedTime = computed(() => {
  const minutes = Math.floor(remainingSeconds.value / 60);
  const seconds = remainingSeconds.value % 60;

  return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
});

const progress = computed(() => {
  const total = mode.value === "focus" ? FOCUS_SECONDS : BREAK_SECONDS;
  return (total - remainingSeconds.value) / total;
});

function stopTimer() {
  if (timer !== null) {
    clearInterval(timer);
    timer = null;
  }

  isRunning.value = false;
}

function switchMode(nextMode: TimerMode) {
  stopTimer();
  mode.value = nextMode;
  remainingSeconds.value = nextMode === "focus" ? FOCUS_SECONDS : BREAK_SECONDS;
}

async function startTimer() {
  if (isRunning.value) return;

  isRunning.value = true;

  timer = setInterval(() => {
    if (remainingSeconds.value > 0) {
      remainingSeconds.value--;
    }

    if (remainingSeconds.value === 0) {
      const completedMode = mode.value;
      const duration = completedMode === "focus" ? 25 : 5;
      const completedAt = new Date().toISOString();

      stopTimer();

      void saveFocusSession({
        task_id: completedMode === "focus" ? selectedTaskId.value : null,
        duration_minutes: duration,
        session_type: completedMode,
        is_completed: true,
        started_at: new Date(Date.now() - duration * 60 * 1000).toISOString(),
        completed_at: completedAt
      })
        .then(async () => {
          await loadSessionHistory();

          Notify.create({
            type: "positive",
            message:
              completedMode === "focus"
                ? "Focus session completed and saved!"
                : "Break completed and saved!"
          });
        })
        .catch(() => {
          Notify.create({
            type: "negative",
            message: "Session completed, but could not be saved."
          });
        });

      if (completedMode === "focus") {
        switchMode("break");
      } else {
        switchMode("focus");
      }
    }
  }, 1000);
}

function pauseTimer() {
  stopTimer();
}

function resetTimer() {
  stopTimer();
  remainingSeconds.value =
    mode.value === "focus" ? FOCUS_SECONDS : BREAK_SECONDS;
}

onUnmounted(() => {
  stopTimer();
});
</script>

<template>
  <q-page
    class="pomodoro-page q-pa-lg"
    :class="{ 'pomodoro-dark': $q.dark.isActive }"
  >
    <div class="pomodoro-container">
      <div class="q-mb-lg">
        <q-select
          v-model="selectedTaskId"
          :options="taskOptions"
          outlined
          dense
          clearable
          emit-value
          map-options
          label="What task are you working on?"
          placeholder="Select a task (optional)"
          style="max-width: 350px; margin: 0 auto"
        />
      </div>
      <div class="row items-center q-gutter-sm">
        <q-btn
          flat
          round
          dense
          icon="arrow_back"
          color="primary"
          aria-label="Back to My Tasks"
          @click="$router.push('/')"
        />

        <div class="text-h4 text-weight-bold"> Pomodoro Focus Timer </div>
      </div>

      <div class="text-subtitle1 text-grey q-mt-sm">
        Stay focused, take breaks, and make progress.
      </div>

      <q-card class="timer-card q-mt-xl">
        <q-card-section class="text-center">
          <div class="row justify-center q-gutter-sm">
            <q-btn
              label="Focus"
              :color="mode === 'focus' ? 'primary' : 'grey-5'"
              :outline="mode !== 'focus'"
              rounded
              @click="switchMode('focus')"
            />
            <q-btn
              label="Break"
              :color="mode === 'break' ? 'secondary' : 'grey-5'"
              :outline="mode !== 'break'"
              rounded
              @click="switchMode('break')"
            />
          </div>

          <div class="timer-circle q-mt-xl">
            <q-circular-progress
              :value="progress * 100"
              size="250px"
              :thickness="0.09"
              color="primary"
              track-color="grey-3"
              show-value
            >
              <div class="text-center">
                <div class="timer-time">{{ formattedTime }}</div>
                <div class="text-subtitle2 text-grey">
                  {{ mode === "focus" ? "FOCUS TIME" : "BREAK TIME" }}
                </div>
              </div>
            </q-circular-progress>
          </div>

          <div class="row justify-center q-gutter-sm q-mt-xl">
            <q-btn
              :label="isRunning ? 'Running' : 'Start'"
              icon="play_arrow"
              color="primary"
              unelevated
              rounded
              :disable="isRunning"
              @click="startTimer"
            />
            <q-btn
              label="Pause"
              icon="pause"
              color="grey-7"
              outline
              rounded
              :disable="!isRunning"
              @click="pauseTimer"
            />
            <q-btn
              label="Reset"
              icon="restart_alt"
              color="grey-7"
              outline
              rounded
              @click="resetTimer"
            />
          </div>
        </q-card-section>
      </q-card>

      <div class="row justify-center q-mt-lg">
        <q-card class="sessions-card text-center">
          <q-card-section>
            <q-icon name="task_alt" size="32px" color="positive" />
            <div class="text-h4 text-weight-bold">
              {{ completedSessions }}
            </div>
            <div class="text-caption text-grey">
              Completed focus sessions this visit
            </div>
          </q-card-section>
        </q-card>
      </div>
      <q-card class="history-card q-mt-lg">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            <q-icon name="history" class="q-mr-sm" />
            Focus Session History
          </div>

          <div v-if="sessionHistory.length === 0" class="text-grey q-mt-md">
            No sessions recorded yet. Complete your first Pomodoro session!
          </div>

          <q-list v-else separator class="q-mt-md">
            <q-item v-for="session in sessionHistory" :key="session.id">
              <q-item-section avatar>
                <q-icon
                  :name="
                    session.session_type === 'focus' ? 'psychology' : 'coffee'
                  "
                  :color="
                    session.session_type === 'focus' ? 'primary' : 'secondary'
                  "
                  size="28px"
                />
              </q-item-section>

              <q-item-section>
                <q-item-label>
                  {{
                    session.session_type === "focus"
                      ? "Focus Session"
                      : "Break Session"
                  }}
                </q-item-label>

                <q-item-label caption>
                  {{ session.task?.title || "No task assigned" }}
                </q-item-label>

                <q-item-label caption>
                  {{
                    session.started_at
                      ? new Date(session.started_at).toLocaleString()
                      : "Time unavailable"
                  }}
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-badge
                  :color="session.is_completed ? 'positive' : 'grey'"
                  :label="session.is_completed ? 'Completed' : 'Incomplete'"
                />
                <div class="text-caption q-mt-xs">
                  {{ session.duration_minutes }} min
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<style scoped>
.pomodoro-page {
  min-height: 100%;
  background: #f8f5ff;
  color: #29243a;
  transition:
    background 0.3s ease,
    color 0.3s ease;
}

.pomodoro-container {
  max-width: 850px;
  margin: 0 auto;
}

.timer-card,
.sessions-card,
.history-card {
  border-radius: 24px;
  background: #ffffff !important;
  color: #29243a !important;
  box-shadow: 0 8px 30px rgba(100, 80, 140, 0.08);
}

.timer-card {
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.timer-time {
  font-size: 48px;
  font-weight: 700;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}

.sessions-card {
  min-width: 220px;
}

/* DARK MODE */
.pomodoro-page.pomodoro-dark {
  background: #1d1b27 !important;
  color: #f5f0ff !important;
}

.pomodoro-dark .timer-card,
.pomodoro-dark .sessions-card,
.pomodoro-dark .history-card {
  background: #292536 !important;
  color: #f5f0ff !important;
}

@media (max-width: 600px) {
  .timer-time {
    font-size: 40px;
  }

  .pomodoro-page {
    padding: 16px;
  }
}
</style>
