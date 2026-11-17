import { api } from "@/boot/axios";

export interface TaskNotification {
  id: number;
  task_id: number;
  notification_type: "due_today" | "overdue";
  notified_for: string;
  sent_at: string;
  read_at: string | null;
  task: {
    id: number;
    title: string;
    description: string | null;
    due_date: string;
    priority: string;
    status: string;
  } | null;
}

export interface NotificationResponse {
  notifications: {
    data: TaskNotification[];
    current_page: number;
    last_page: number;
    total: number;
  };
  unread_count: number;
}

export async function getNotifications(
  page = 1,
  filters?: {
    type?: string;
    status?: string;
  }
): Promise<NotificationResponse> {
  const response = await api.get<NotificationResponse>("/notifications", {
    params: {
      page,
      type: filters?.type || undefined,
      status: filters?.status || undefined
    }
  });

  return response.data;
}

export async function markNotificationAsRead(id: number) {
  const response = await api.patch(`/notifications/${id}/read`);
  return response.data;
}

export async function markAllNotificationsAsRead() {
  const response = await api.patch("/notifications/read-all");
  return response.data;
}
