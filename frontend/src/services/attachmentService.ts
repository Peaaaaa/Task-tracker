import { api } from "../boot/axios";

export interface TaskAttachment {
  id: number;
  task_id: number;
  original_name: string;
  file_path: string;
  cloudinary_url: string | null;
  cloudinary_public_id?: string | null;
  mime_type: string | null;
  file_size: number;
  created_at: string;
  updated_at: string;
}

export const attachmentService = {
  async getAttachments(taskId: number): Promise<TaskAttachment[]> {
    const response = await api.get(`/tasks/${taskId}/attachments`);
    return response.data;
  },

  async uploadAttachment(taskId: number, file: File): Promise<TaskAttachment> {
    const formData = new FormData();
    formData.append("file", file);

    const response = await api.post(`/tasks/${taskId}/attachments`, formData);

    return response.data.attachment;
  },

  async deleteAttachment(attachmentId: number): Promise<void> {
    await api.delete(`/attachments/${attachmentId}`);
  }
};
