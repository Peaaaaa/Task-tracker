<template>
  <q-page
    class="goals-page q-pa-lg"
    :class="{ 'goals-dark': $q.dark.isActive }"
  >
    <div class="goals-container">
      <!-- Header -->

      <div class="row items-center justify-between q-mb-lg">
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

          <div>
            <div class="text-h4 text-weight-bold">My Goals ✨</div>
            <div class="text-subtitle2 text-grey-7 q-mt-xs">
              Turn your dreams into achievable milestones.
            </div>
          </div>
        </div>
        <div class="text-subtitle2 text-grey-7 q-mt-xs"> </div>
      </div>

      <q-btn
        color="primary"
        unelevated
        rounded
        icon="add"
        class="q-mb-lg"
        label="New Goal"
        @click="openCreateDialog"
      />
    </div>

    <!-- Summary -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-4">
        <q-card class="summary-card">
          <q-card-section>
            <div class="text-grey-7">Total Goals</div>
            <div class="text-h4 text-weight-bold">{{ goals.length }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-4">
        <q-card class="summary-card">
          <q-card-section>
            <div class="text-grey-7">In Progress</div>
            <div class="text-h4 text-weight-bold text-primary">
              {{ goals.filter(goal => goal.status === "in_progress").length }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-4">
        <q-card class="summary-card">
          <q-card-section>
            <div class="text-grey-7">Completed</div>
            <div class="text-h4 text-weight-bold text-positive">
              {{ goals.filter(goal => goal.status === "completed").length }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner-dots color="primary" size="40px" />
      <div class="q-mt-sm text-grey-7">Loading your goals...</div>
    </div>

    <!-- Empty state -->
    <q-card v-else-if="goals.length === 0" class="empty-card">
      <q-card-section class="text-center q-pa-xl">
        <q-icon name="flag" size="64px" color="primary" />
        <div class="text-h6 q-mt-md">No goals yet</div>
        <div class="text-grey-7 q-mb-md">
          Start by creating your first personal goal.
        </div>
        <q-btn
          color="primary"
          rounded
          unelevated
          label="Create My First Goal"
          @click="openCreateDialog"
        />
      </q-card-section>
    </q-card>

    <!-- Goals list -->
    <div v-else class="row q-col-gutter-md">
      <div
        v-for="goal in goals"
        :key="goal.id"
        class="col-12 col-md-6 col-lg-4"
      >
        <q-card class="goal-card">
          <q-card-section>
            <div class="row items-start justify-between">
              <div class="col">
                <div class="text-h6 text-weight-bold">
                  {{ goal.title }}
                </div>
                <div class="text-caption text-grey-7 q-mt-xs">
                  {{ goal.description || "No description provided." }}
                </div>
              </div>

              <q-btn flat round dense icon="more_vert">
                <q-menu>
                  <q-list style="min-width: 130px">
                    <q-item
                      clickable
                      v-close-popup
                      @click="openEditDialog(goal)"
                    >
                      <q-item-section avatar>
                        <q-icon name="edit" />
                      </q-item-section>
                      <q-item-section>Edit</q-item-section>
                    </q-item>

                    <q-item clickable v-close-popup @click="deleteGoal(goal)">
                      <q-item-section avatar>
                        <q-icon name="delete" color="negative" />
                      </q-item-section>
                      <q-item-section class="text-negative"
                        >Delete</q-item-section
                      >
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </div>

            <div class="q-mt-md">
              <q-badge
                :color="statusColor(goal.status)"
                :label="statusLabel(goal.status)"
              />
            </div>

            <div class="row justify-between items-center q-mt-lg q-mb-sm">
              <span class="text-weight-medium">Goal Progress</span>
              <span class="text-weight-bold text-primary">
                {{ goal.progress ?? 0 }}%
              </span>
            </div>

            <q-linear-progress
              rounded
              size="10px"
              :value="(goal.progress ?? 0) / 100"
              color="primary"
              track-color="grey-3"
            />

            <div class="row justify-between text-caption text-grey-7 q-mt-md">
              <span>
                <q-icon name="flag" size="xs" />
                {{ goal.milestones?.length ?? 0 }} milestones
              </span>
              <span>
                <q-icon name="event" size="xs" />
                {{ formatDate(goal.target_date) }}
              </span>
            </div>

            <q-btn
              flat
              color="primary"
              class="full-width q-mt-md"
              label="View Goal Details"
              icon-right="arrow_forward"
              @click="openGoalDetails(goal)"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Create/Edit Goal Dialog -->
    <q-dialog v-model="showGoalDialog">
      <q-card style="width: 500px; max-width: 95vw; border-radius: 18px">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            {{ editingGoal ? "Edit Goal" : "Create a New Goal" }}
          </div>
          <div class="text-caption text-grey-7">
            Define what you want to accomplish.
          </div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.title"
            outlined
            label="Goal Title"
            placeholder="Example: Finish my school project"
          />

          <q-input
            v-model="form.description"
            outlined
            type="textarea"
            label="Description"
          />

          <q-input
            v-model="form.target_date"
            outlined
            type="date"
            label="Target Date"
          />

          <q-select
            v-model="form.status"
            outlined
            emit-value
            map-options
            label="Status"
            :options="statusOptions"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            color="primary"
            unelevated
            rounded
            label="Save Goal"
            :loading="saving"
            @click="saveGoal"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Goal Details Dialog -->
    <q-dialog v-model="showDetailsDialog">
      <q-card style="width: 600px; max-width: 95vw; border-radius: 18px">
        <q-card-section v-if="selectedGoal">
          <div class="row items-center justify-between">
            <div class="text-h6 text-weight-bold">
              {{ selectedGoal.title }}
            </div>
            <q-btn flat round dense icon="close" v-close-popup />
          </div>

          <p class="text-grey-7 q-mt-sm">
            {{ selectedGoal.description || "No description provided." }}
          </p>

          <div class="text-weight-medium q-mb-sm">
            Progress: {{ selectedGoal.progress ?? 0 }}%
          </div>

          <q-linear-progress
            rounded
            size="12px"
            :value="(selectedGoal.progress ?? 0) / 100"
            color="primary"
          />

          <div class="text-subtitle1 text-weight-bold q-mt-lg">
            Milestones
          </div>

          <div
            v-if="!selectedGoal.milestones?.length"
            class="text-grey-7 q-mt-sm"
          >
            No milestones added yet.
          </div>

          <q-list separator>
            <q-item
              v-for="milestone in selectedGoal.milestones"
              :key="milestone.id"
            >
              <q-item-section side>
                <q-checkbox
                  :model-value="milestone.is_completed"
                  @update:model-value="toggleMilestone(milestone)"
                />
              </q-item-section>

              <q-item-section>
                <q-item-label
                  :class="{ 'text-strike text-grey-6': milestone.is_completed }"
                >
                  {{ milestone.title }}
                </q-item-label>
                <q-item-label caption>
                  {{ formatDate(milestone.target_date) }}
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-btn
                  flat
                  round
                  dense
                  icon="delete"
                  color="negative"
                  @click="deleteMilestone(milestone.id)"
                />
              </q-item-section>
            </q-item>
          </q-list>

          <div class="row q-gutter-sm q-mt-md">
            <q-input
              v-model="milestoneTitle"
              outlined
              dense
              class="col"
              label="New milestone"
              @keyup.enter="addMilestone"
            />
            <q-btn
              color="primary"
              icon="add"
              round
              :disable="!milestoneTitle.trim()"
              @click="addMilestone"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { useQuasar } from "quasar";

const $q = useQuasar();
import { api } from "@/boot/axios";
import { Notify, Dialog } from "quasar";

interface Milestone {
  id: number;
  title: string;
  description: string | null;
  target_date: string | null;
  is_completed: boolean;
}

interface Goal {
  id: number;
  title: string;
  description: string | null;
  target_date: string | null;
  status: "not_started" | "in_progress" | "completed" | "cancelled";
  progress: number;
  milestones: Milestone[];
  tasks: unknown[];
}

const goals = ref<Goal[]>([]);
const loading = ref(false);
const saving = ref(false);
const showGoalDialog = ref(false);
const showDetailsDialog = ref(false);
const editingGoal = ref<Goal | null>(null);
const selectedGoal = ref<Goal | null>(null);
const milestoneTitle = ref("");

const form = reactive({
  title: "",
  description: "",
  target_date: "",
  status: "not_started" as Goal["status"]
});

const statusOptions = [
  { label: "Not Started", value: "not_started" },
  { label: "In Progress", value: "in_progress" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" }
];

function statusColor(status: Goal["status"]) {
  return {
    not_started: "grey",
    in_progress: "primary",
    completed: "positive",
    cancelled: "negative"
  }[status];
}

function statusLabel(status: Goal["status"]) {
  return {
    not_started: "Not Started",
    in_progress: "In Progress",
    completed: "Completed",
    cancelled: "Cancelled"
  }[status];
}

function formatDate(date: string | null) {
  if (!date) return "No target date";
  return new Date(`${date.slice(0, 10)}T00:00:00`).toLocaleDateString();
}

async function loadGoals() {
  loading.value = true;
  try {
    const response = await api.get("/goals");
    goals.value = response.data;
  } catch (error) {
    console.error(error);
    Notify.create({ type: "negative", message: "Unable to load goals." });
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  form.title = "";
  form.description = "";
  form.target_date = "";
  form.status = "not_started";
  editingGoal.value = null;
}

function openCreateDialog() {
  resetForm();
  showGoalDialog.value = true;
}

function openEditDialog(goal: Goal) {
  editingGoal.value = goal;
  form.title = goal.title;
  form.description = goal.description ?? "";
  form.target_date = goal.target_date?.slice(0, 10) ?? "";
  form.status = goal.status;
  showGoalDialog.value = true;
}

async function saveGoal() {
  if (!form.title.trim()) {
    Notify.create({ type: "warning", message: "Please enter a goal title." });
    return;
  }

  saving.value = true;

  const payload = {
    title: form.title,
    description: form.description || null,
    target_date: form.target_date || null,
    status: form.status
  };

  try {
    if (editingGoal.value) {
      await api.put(`/goals/${editingGoal.value.id}`, payload);
      Notify.create({
        type: "positive",
        message: "Goal updated successfully."
      });
    } else {
      await api.post("/goals", payload);
      Notify.create({
        type: "positive",
        message: "Goal created successfully."
      });
    }

    showGoalDialog.value = false;
    resetForm();
    await loadGoals();
  } catch (error) {
    console.error(error);
    Notify.create({ type: "negative", message: "Unable to save goal." });
  } finally {
    saving.value = false;
  }
}

function deleteGoal(goal: Goal) {
  Dialog.create({
    title: "Delete Goal",
    message: `Are you sure you want to delete "${goal.title}"? Its milestones will also be deleted. Tasks linked to it will become unassigned.`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.delete(`/goals/${goal.id}`);
      Notify.create({ type: "positive", message: "Goal deleted." });
      await loadGoals();
    } catch (error) {
      console.error(error);
      Notify.create({ type: "negative", message: "Unable to delete goal." });
    }
  });
}

async function openGoalDetails(goal: Goal) {
  try {
    const response = await api.get(`/goals/${goal.id}`);
    selectedGoal.value = response.data;
    showDetailsDialog.value = true;
  } catch (error) {
    console.error(error);
    Notify.create({
      type: "negative",
      message: "Unable to load goal details."
    });
  }
}

async function addMilestone() {
  if (!selectedGoal.value || !milestoneTitle.value.trim()) return;

  try {
    await api.post(`/goals/${selectedGoal.value.id}/milestones`, {
      title: milestoneTitle.value.trim()
    });

    milestoneTitle.value = "";
    await refreshSelectedGoal();
    await loadGoals();
    Notify.create({ type: "positive", message: "Milestone added." });
  } catch (error) {
    console.error(error);
    Notify.create({ type: "negative", message: "Unable to add milestone." });
  }
}

async function toggleMilestone(milestone: Milestone) {
  try {
    await api.patch(`/milestones/${milestone.id}`, {
      is_completed: !milestone.is_completed
    });
    await refreshSelectedGoal();
    await loadGoals();
  } catch (error) {
    console.error(error);
    Notify.create({ type: "negative", message: "Unable to update milestone." });
  }
}

async function deleteMilestone(id: number) {
  try {
    await api.delete(`/milestones/${id}`);
    await refreshSelectedGoal();
    await loadGoals();
    Notify.create({ type: "positive", message: "Milestone deleted." });
  } catch (error) {
    console.error(error);
    Notify.create({ type: "negative", message: "Unable to delete milestone." });
  }
}

async function refreshSelectedGoal() {
  if (!selectedGoal.value) return;
  const response = await api.get(`/goals/${selectedGoal.value.id}`);
  selectedGoal.value = response.data;
}

onMounted(loadGoals);
</script>

<style scoped>
.goals-page {
  background: #f8f6ff;
  color: #29223a;
  min-height: 100%;
  transition:
    background 0.25s ease,
    color 0.25s ease;
}

.goals-container {
  max-width: 1200px;
  margin: 0 auto;
}

/* Light mode */
.summary-card,
.goal-card,
.empty-card {
  background: #ffffff;
  color: #29223a;
  border-radius: 18px;
  border: 1px solid #eee8ff;
  box-shadow: 0 4px 16px rgba(105, 75, 160, 0.06);
}

/* Dark mode */
.goals-page.goals-dark {
  background: #171322;
  color: #f5f0ff;
}

.goals-dark .summary-card,
.goals-dark .goal-card,
.goals-dark .empty-card {
  background: #29223a;
  color: #f5f0ff;
  border-color: #403653;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.goals-dark .text-grey-7 {
  color: #c4b8d8 !important;
}

.goal-card {
  height: 100%;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.goal-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 22px rgba(105, 75, 160, 0.12);
}

@media (max-width: 600px) {
  .goals-page {
    padding: 16px !important;
  }
}
</style>
