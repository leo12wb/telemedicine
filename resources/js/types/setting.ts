export type VideoProvider = 'none' | 'jitsi' | 'daily'

export interface Settings {
  video_provider: VideoProvider
  jitsi_server_url?: string
  daily_api_key?: string
  daily_domain?: string
}
