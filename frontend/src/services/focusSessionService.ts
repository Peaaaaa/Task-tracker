import { api } from "@/boot/axios";

export interface FocusSession {
  id: number;
  task_id: number | null;
  duration_minutes: number;
  session_type: "focus" | "break";
  is_completed: boolean;
  started_at: string | null;
  completed_at: string | null;
  created_at: string;
  task?: {
    id: number;
    title: string;
  } | null;
}

export interface FocusSessionResponse {
  sessions: FocusSession[];
  completed_focus_sessions: number;
}

export interface CreateFocusSessionData {
  task_id: number | null;
  duration_minutes: number;
  session_type: "focus" | "break";
  is_completed: boolean;
  started_at: string;
  completed_at: string | null;
}

export async function getFocusSessions() {
  const response = await api.get<FocusSessionResponse>("/focus-sessions");
  return response.data;
}

export async function saveFocusSession(data: CreateFocusSessionData) {
  const response = await api.post("/focus-sessions", data);
  return response.data;
}
