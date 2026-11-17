<template>
  <q-btn flat round icon="notifications" aria-label="Notifications">
    <q-badge v-if="unreadCount > 0" color="negative" floating rounded>
      {{ unreadCount > 99 ? "99+" : unreadCount }}
    </q-badge>

    <q-menu
      anchor="bottom right"
      self="top right"
      :offset="[0, 12]"
      @show="loadNotifications"
    >
      <q-card class="notification-menu">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Notifications</div>
            <div class="text-caption text-grey-6">
              {{ unreadCount }} unread notifications
            </div>
          </div>

          <q-btn
            v-if="unreadCount > 0"
            flat
            dense
            no-caps
            color="primary"
            label="Mark all read"
            :loading="markingAll"
            @click="markAllRead"
          />
        </q-card-section>

        <q-separator />

        <q-card-section v-if="loading" class="text-center q-pa-lg">
          <q-spinner color="primary" size="32px" />
          <div class="q-mt-sm text-grey-6">Loading notifications...</div>
        </q-card-section>

        <q-card-section
          v-else-if="notifications.length === 0"
          class="text-center q-pa-xl"
        >
          <q-icon name="notifications_none" size="48px" color="grey-5" />
          <div class="text-subtitle1 q-mt-sm"> You're all caught up! </div>
          <div class="text-caption text-grey-6">No notifications yet.</div>
        </q-card-section>

        <q-list v-else separator>
          <q-item
            v-for="notification in notifications"
            :key="notification.id"
            :class="{ 'notification-unread': !notification.read_at }"
            clickable
            @click="markRead(notification)"
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
              >
                <q-icon
                  :name="
                    notification.notification_type === 'overdue'
                      ? 'warning'
                      : 'event'
                  "
                />
              </q-avatar>
            </q-item-section>

            <q-item-section>
              <q-item-label class="text-weight-medium">
                {{
                  notification.notification_type === "overdue"
                    ? "Overdue Task"
                    : "Task Due Today"
                }}
              </q-item-label>

              <q-item-label caption>
                {{ notification.task?.title || "Task unavailable" }}
              </q-item-label>

              <q-item-label caption class="q-mt-xs">
                {{ formatDate(notification.sent_at) }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-badge v-if="!notification.read_at" color="primary" rounded>
                New
              </q-badge>
            </q-item-section>
          </q-item>
        </q-list>

        <q-separator />

        <q-card-actions align="between">
          <q-btn
            flat
            no-caps
            color="primary"
            label="View all notifications"
            icon="open_in_new"
            to="/notifications"
          />

          <q-btn
            flat
            no-caps
            color="primary"
            label="Refresh"
            icon="refresh"
            :loading="loading"
            @click="loadNotifications"
          />
        </q-card-actions>
      </q-card>
    </q-menu>
  </q-btn>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Notify } from "quasar";
import {
  getNotifications,
  markNotificationAsRead,
  markAllNotificationsAsRead,
  type TaskNotification
} from "@/services/notificationService";

const notifications = ref<TaskNotification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);
const markingAll = ref(false);

async function loadNotifications() {
  loading.value = true;

  try {
    const response = await getNotifications();

    notifications.value = response.notifications.data;
    unreadCount.value = response.unread_count;
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
      message: "Unable to update notification."
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
    console.error("Failed to mark all as read:", error);

    Notify.create({
      type: "negative",
      message: "Unable to update notifications."
    });
  } finally {
    markingAll.value = false;
  }
}

function formatDate(value: string) {
  return new Date(value).toLocaleString("en-PH", {
    dateStyle: "medium",
    timeStyle: "short"
  });
}

onMounted(loadNotifications);
</script>

<style scoped>
.notification-menu {
  width: 360px;
  max-width: calc(100vw - 32px);
  border-radius: 16px;
}

.notification-unread {
  background: rgba(155, 135, 213, 0.1);
}

@media (max-width: 480px) {
  .notification-menu {
    width: 320px;
  }
}
</style>
