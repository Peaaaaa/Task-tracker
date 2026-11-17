<template>
  <q-page class="notifications-page q-pa-md q-pa-lg-lg">
    <div class="page-header q-mb-lg">
      <div class="row items-center q-gutter-sm">
        <q-btn
          flat
          round
          dense
          icon="arrow_back"
          aria-label="Back to Dashboard"
          @click="$router.push('/dashboard')"
        >
          <q-tooltip>Back to Dashboard</q-tooltip>
        </q-btn>

        <div>
          <div class="text-h4 text-weight-bold"> Notification History </div>
          <div class="text-subtitle2 text-grey-6 q-mt-xs">
            Keep track of your task reminders and updates.
          </div>
        </div>
      </div>

      <div class="row items-center q-gutter-sm">
        <q-btn
          outline
          rounded
          color="primary"
          icon="refresh"
          label="Refresh"
          :loading="loading"
          @click="() => loadNotifications()"
        />

        <q-btn
          v-if="unreadCount > 0"
          unelevated
          rounded
          color="primary"
          icon="done_all"
          label="Mark all as read"
          :loading="markingAll"
          @click="markAllRead"
        />
      </div>
    </div>

    <!-- Summary -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-4">
        <q-card class="summary-card">
          <q-card-section>
            <div class="row items-center justify-between">
              <div>
                <div class="text-caption text-grey-6">Total notifications</div>
                <div class="text-h4 text-weight-bold">{{ total }}</div>
              </div>
              <q-avatar
                color="deep-purple-1"
                text-color="deep-purple-8"
                size="48px"
              >
                <q-icon name="notifications" size="26px" />
              </q-avatar>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-4">
        <q-card class="summary-card">
          <q-card-section>
            <div class="row items-center justify-between">
              <div>
                <div class="text-caption text-grey-6">Unread</div>
                <div class="text-h4 text-weight-bold">{{ unreadCount }}</div>
              </div>
              <q-avatar color="purple-1" text-color="purple-8" size="48px">
                <q-icon name="mark_email_unread" size="26px" />
              </q-avatar>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-4">
        <q-card class="summary-card">
          <q-card-section>
            <div class="row items-center justify-between">
              <div>
                <div class="text-caption text-grey-6">Read</div>
                <div class="text-h4 text-weight-bold">{{
                  total - unreadCount
                }}</div>
              </div>
              <q-avatar color="green-1" text-color="green-8" size="48px">
                <q-icon name="mark_email_read" size="26px" />
              </q-avatar>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Filters -->
    <q-card class="history-card">
      <q-card-section class="row items-center justify-between q-gutter-md">
        <div class="text-h6 text-weight-bold">All Notifications</div>

        <div class="row items-center q-gutter-sm">
          <q-select
            v-model="typeFilter"
            dense
            outlined
            emit-value
            map-options
            :options="typeOptions"
            label="Type"
            style="min-width: 150px"
          />

          <q-select
            v-model="readFilter"
            dense
            outlined
            emit-value
            map-options
            :options="readOptions"
            label="Status"
            style="min-width: 130px"
          />
        </div>
      </q-card-section>

      <q-separator />

      <q-inner-loading :showing="loading">
        <q-spinner color="primary" size="40px" />
      </q-inner-loading>

      <div
        v-if="!loading && filteredNotifications.length === 0"
        class="empty-state"
      >
        <q-icon name="notifications_none" size="64px" color="grey-5" />
        <div class="text-h6 q-mt-md">No notifications found</div>
        <div class="text-caption text-grey-6">
          Try changing your filters or check back later.
        </div>
      </div>

      <q-list v-else-if="!loading" separator>
        <q-item
          v-for="notification in filteredNotifications"
          :key="notification.id"
          class="history-item"
          :class="{ 'history-unread': !notification.read_at }"
        >
          <q-item-section avatar>
            <q-avatar
              :color="
                notification.notification_type === 'overdue'
                  ? 'deep-orange-1'
                  : 'deep-purple-1'
              "
              :text-color="
                notification.notification_type === 'overdue'
                  ? 'deep-orange-9'
                  : 'deep-purple-9'
              "
              size="48px"
            >
              <q-icon
                :name="
                  notification.notification_type === 'overdue'
                    ? 'warning'
                    : 'event'
                "
                size="24px"
              />
            </q-avatar>
          </q-item-section>

          <q-item-section>
            <q-item-label class="text-weight-bold">
              {{
                notification.notification_type === "overdue"
                  ? "Overdue Task Reminder"
                  : "Task Due Today"
              }}
            </q-item-label>

            <q-item-label class="text-body2 q-mt-xs">
              {{ notification.task?.title || "Task unavailable" }}
            </q-item-label>

            <q-item-label caption class="q-mt-xs">
              Due: {{ formatDate(notification.task?.due_date) }} · Received:
              {{ formatDate(notification.sent_at) }}
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <div class="column items-end q-gutter-xs">
              <q-badge
                v-if="!notification.read_at"
                color="primary"
                label="Unread"
              />
              <q-badge v-else color="grey-6" label="Read" />

              <q-btn
                v-if="!notification.read_at"
                flat
                dense
                no-caps
                color="primary"
                label="Mark as read"
                @click="markRead(notification)"
              />
            </div>
          </q-item-section>
        </q-item>
      </q-list>

      <q-separator />

      <q-card-section class="row items-center justify-between">
        <div class="text-caption text-grey-6">
          Showing {{ filteredNotifications.length }} of
          {{ total }} notifications
        </div>

        <div class="row items-center q-gutter-sm">
          <q-btn
            flat
            round
            icon="chevron_left"
            :disable="currentPage <= 1 || loading"
            @click="changePage(currentPage - 1)"
          />

          <span class="text-caption">
            Page {{ currentPage }} of {{ lastPage }}
          </span>

          <q-btn
            flat
            round
            icon="chevron_right"
            :disable="currentPage >= lastPage || loading"
            @click="changePage(currentPage + 1)"
          />
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { Notify } from "quasar";
import {
  getNotifications,
  markNotificationAsRead,
  markAllNotificationsAsRead,
  type TaskNotification
} from "@/services/notificationService";

const notifications = ref<TaskNotification[]>([]);
const unreadCount = ref(0);
const total = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const markingAll = ref(false);

const typeFilter = ref("all");
const readFilter = ref("all");

const typeOptions = [
  { label: "All types", value: "all" },
  { label: "Due Today", value: "due_today" },
  { label: "Overdue", value: "overdue" }
];

const readOptions = [
  { label: "All statuses", value: "all" },
  { label: "Unread", value: "unread" },
  { label: "Read", value: "read" }
];

const filteredNotifications = computed(() => {
  return notifications.value.filter(notification => {
    const matchesType =
      typeFilter.value === "all" ||
      notification.notification_type === typeFilter.value;

    const matchesRead =
      readFilter.value === "all" ||
      (readFilter.value === "unread" && !notification.read_at) ||
      (readFilter.value === "read" && !!notification.read_at);

    return matchesType && matchesRead;
  });
});

async function loadNotifications(page = 1) {
  loading.value = true;

  try {
    const response = await getNotifications(page, {
      type: typeFilter.value,
      status: readFilter.value
    });

    notifications.value = response.notifications.data;
    unreadCount.value = response.unread_count;
    total.value = response.notifications.total;
    currentPage.value = response.notifications.current_page;
    lastPage.value = response.notifications.last_page;
  } catch (error) {
    console.error("Failed to load notifications:", error);

    Notify.create({
      type: "negative",
      message: "Unable to load notifications."
    });
  } finally {
    loading.value = false;
  }
}

async function markRead(notification: TaskNotification) {
  if (notification.read_at) return;

  try {
    await markNotificationAsRead(notification.id);

    notification.read_at = new Date().toISOString();
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  } catch (error) {
    console.error("Failed to mark notification as read:", error);

    Notify.create({
      type: "negative",
      message: "Unable to mark notification as read."
    });
  }
}

async function markAllRead() {
  markingAll.value = true;

  try {
    await markAllNotificationsAsRead();

    const now = new Date().toISOString();

    notifications.value.forEach(notification => {
      notification.read_at = notification.read_at || now;
    });

    unreadCount.value = 0;

    Notify.create({
      type: "positive",
      message: "All notifications marked as read."
    });
  } catch (error) {
    console.error("Failed to mark all notifications as read:", error);

    Notify.create({
      type: "negative",
      message: "Unable to update notifications."
    });
  } finally {
    markingAll.value = false;
  }
}

function formatDate(value?: string | null) {
  if (!value) return "Unavailable";

  return new Date(value).toLocaleString("en-PH", {
    dateStyle: "medium",
    timeStyle: "short"
  });
}

function changePage(page: number) {
  if (page < 1 || page > lastPage.value || loading.value) return;

  loadNotifications(page);
}
watch([typeFilter, readFilter], () => {
  loadNotifications(1);
});

onMounted(() => loadNotifications());
</script>

<style scoped>
.notifications-page {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.summary-card,
.history-card {
  border-radius: 16px;
}

.history-item {
  padding-top: 16px;
  padding-bottom: 16px;
}

.history-unread {
  background: rgba(155, 135, 213, 0.08);
}

.empty-state {
  text-align: center;
  padding: 64px 16px;
}

@media (max-width: 600px) {
  .page-header {
    align-items: flex-start;
  }
}
</style>
