import http from './http'

export interface Message {
  id: string
  user_id: string
  user_name: string
  body: string
  created_at: string
}

export interface Attachment {
  id: string
  user_name: string
  original_name: string
  mime_type: string
  size: number
  created_at: string
}

export const appointmentChatService = {
  async getMessages(appointmentId: string): Promise<Message[]> {
    const r = await http.get<{ data: Message[] }>(`/appointments/${appointmentId}/messages`)
    return r.data.data
  },
  async sendMessage(appointmentId: string, body: string): Promise<Message> {
    const r = await http.post<{ data: Message }>(`/appointments/${appointmentId}/messages`, { body })
    return r.data.data
  },
  async getAttachments(appointmentId: string): Promise<Attachment[]> {
    const r = await http.get<{ data: Attachment[] }>(`/appointments/${appointmentId}/attachments`)
    return r.data.data
  },
  async uploadAttachment(appointmentId: string, file: File): Promise<Attachment> {
    const form = new FormData()
    form.append('file', file)
    const r = await http.post<{ data: Attachment }>(`/appointments/${appointmentId}/attachments`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return r.data.data
  },
  downloadUrl(appointmentId: string, attachmentId: string): string {
    return `/api/v1/appointments/${appointmentId}/attachments/${attachmentId}/download`
  },
  async deleteAttachment(appointmentId: string, attachmentId: string): Promise<void> {
    await http.delete(`/appointments/${appointmentId}/attachments/${attachmentId}`)
  },
}
