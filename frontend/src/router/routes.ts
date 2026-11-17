import type { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
  {
    path: "/",
    component: () => import("@/layouts/MainLayout.vue"),
    children: [
      {
        path: "",
        component: () => import("@/pages/IndexPage.vue")
      },
      {
        path: "dashboard",
        component: () => import("@/pages/DashboardPage.vue")
      },
      {
        path: "second",
        component: () => import("@/pages/SecondPage.vue")
      },
      {
        path: "notifications",
        component: () => import("@/pages/NotificationsPage.vue")
      },
      {
        path: "calendar",
        component: () => import("@/pages/CalendarPage.vue")
      },
      {
        path: "goals",
        component: () => import("@/pages/GoalsPage.vue")
      },
      {
        path: "pomodoro",
        component: () => import("@/pages/PomodoroPage.vue")
      }
    ]
  },

  // Always leave this as the last route.
  {
    path: "/:catchAll(.*)*",
    component: () => import("@/pages/ErrorNotFound.vue")
  }
];

export default routes;
