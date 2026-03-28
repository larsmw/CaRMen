import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { User } from '../types'
import client from '../api/client'

interface AuthState {
  token: string | null
  user: User | null
  login: (email: string, password: string) => Promise<void>
  logout: () => void
  fetchMe: () => Promise<void>
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,

      login: async (email, password) => {
        const { data } = await client.post<{ token: string }>('/api/login', { email, password })
        localStorage.setItem('token', data.token)
        set({ token: data.token })
        const me = await client.get<User>('/api/me')
        set({ user: me.data })
      },

      logout: () => {
        localStorage.removeItem('token')
        set({ token: null, user: null })
      },

      fetchMe: async () => {
        const { data } = await client.get<User>('/api/me')
        set({ user: data })
      },
    }),
    { name: 'crm-auth', partialize: (s) => ({ token: s.token }) },
  ),
)
