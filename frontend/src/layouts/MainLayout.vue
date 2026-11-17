<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title> Task Tracker App </q-toolbar-title>

        <!-- Notification Bell -->
        <NotificationCenter />

        <q-btn
          flat
          round
          icon="notifications_active"
          aria-label="Enable notifications"
          @click="showNotificationConfirm = true"
        >
          <q-tooltip> Enable task notifications </q-tooltip>
        </q-btn>

        <!-- Dark Mode Toggle -->
        <q-btn
          flat
          round
          :icon="isDark ? 'light_mode' : 'dark_mode'"
          @click="toggleDarkMode"
        >
          <q-tooltip>
            {{ isDark ? "Switch to light mode" : "Switch to dark mode" }}
          </q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <!-- Notification Confirmation Dialog -->
    <q-dialog v-model="showNotificationConfirm">
      <q-card style="min-width: 300px; max-width: 400px; border-radius: 16px">
        <q-card-section class="text-center">
          <q-icon name="notifications_active" color="primary" size="48px" />

          <div class="text-h6 q-mt-md"> Enable Task Notifications? </div>

          <div class="text-body2 text-grey-7 q-mt-sm">
            Receive reminders about tasks due today and overdue tasks, even when
            Task Tracker is not open.
          </div>
        </q-card-section>

        <q-card-actions align="center" class="q-pb-md">
          <q-btn flat label="Cancel" color="grey" v-close-popup />

          <q-btn
            unelevated
            rounded
            label="Enable Notifications"
            color="primary"
            @click="confirmEnableNotifications"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Sidebar -->
    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <q-list>
        <q-item-label header> Essential Pages </q-item-label>

        <EssentialLink
          v-for="link in linksList"
          :key="link.label"
          v-bind="link"
        />
      </q-list>
    </q-drawer>

    <!-- Main Page -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Dark, Notify } from "quasar";
import { enablePushNotifications } from "@/services/pushNotifications";
import NotificationCenter from "@/components/NotificationCenter.vue";

import EssentialLink, {
  type EssentialLinkProps
} from "@/components/EssentialLink.vue";

// Sidebar links
const linksList: EssentialLinkProps[] = [
  {
    label: "Dashboard",
    caption: "View your productivity summary",
    icon: "dashboard",
    link: "/dashboard"
  },
  {
    label: "Calendar",
    caption: "View your task calendar",
    icon: "calendar_month",
    link: "/calendar"
  },

  {
    label: "Goals",
    caption: "View your goals and milestones",
    icon: "flag",
    link: "/goals"
  },
  {
    label: "Pomodoro Timer",
    caption: "Focus on your tasks with Pomodoro technique",
    icon: "timer",
    link: "/pomodoro"
  },

  {
    label: "GitHub",
    caption: "github.com/Peaaaaa",
    icon: "code",
    link: "https://github.com/Peaaaaa"
  },
  {
    label: "Discord Chat Channel",
    caption: "IT students discord channel",
    icon: "chat",
    link: "https://discord.gg/VCQWmtZ4"
  },
  {
    label: "Facebook",
    caption: "@JinkyPearlAguilar",
    icon: "public",
    link: "https://www.facebook.com/pearly.ae/"
  }
];

// Dialog state
const showNotificationConfirm = ref(false);

// Dark mode state
const isDark = ref(Dark.isActive);

// Sidebar state
const leftDrawerOpen = ref(false);

// Toggle dark mode
function toggleDarkMode() {
  Dark.toggle();
  isDark.value = Dark.isActive;

  localStorage.setItem("task-tracker-theme", Dark.isActive ? "dark" : "light");
}

// Enable notifications after user confirms
async function confirmEnableNotifications() {
  showNotificationConfirm.value = false;

  await handleEnableNotifications();
}

// Handle browser push notification registration

async function handleEnableNotifications() {
  try {
    await enablePushNotifications();

    Notify.create({
      type: "positive",
      message: "Task notifications enabled successfully!",
      position: "top",
      timeout: 3000,
      color: "purple-4",
      textColor: "white",
      icon: "check_circle",
      classes: "task-notification-toast"
    });
  } catch (error) {
    console.error("Notification setup failed:", error);

    Notify.create({
      type: "negative",
      message:
        error instanceof Error
          ? error.message
          : "Unable to enable notifications.",
      position: "top",
      timeout: 4000,
      color: "deep-purple-5",
      textColor: "white",
      icon: "error",
      classes: "task-notification-toast"
    });
  }
}

// Restore saved theme
onMounted(() => {
  const savedTheme = localStorage.getItem("task-tracker-theme");

  if (savedTheme === "dark") {
    Dark.set(true);
    isDark.value = true;
  } else if (savedTheme === "light") {
    Dark.set(false);
    isDark.value = false;
  }
});

// Toggle sidebar
function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value;
}
</script>
