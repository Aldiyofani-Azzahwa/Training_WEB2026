import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'

import { http } from '@/services/http'

import type {
  ApiResponse,
  AuthUser,
  LoginPayload,
  UserRole,
} from '@/types/auth'

export const useAuthStore = defineStore(
  'auth',
  () => {
    const user = ref<AuthUser | null>(null)
    const initialized = ref(false)
    const loading = ref(false)

    const isAuthenticated = computed(
      () => user.value !== null,
    )

    const role = computed<UserRole | null>(
      () => user.value?.role ?? null,
    )

    /**
     * Mengambil pengguna yang memiliki session aktif.
     */
    async function fetchUser(): Promise<void> {
      loading.value = true

      try {
        const response =
          await http.get<ApiResponse<AuthUser>>(
            '/api/v1/auth/me',
          )

        user.value = response.data.data
      } catch (error) {
        if (
          axios.isAxiosError(error) &&
          error.response?.status === 401
        ) {
          user.value = null
          return
        }

        throw error
      } finally {
        initialized.value = true
        loading.value = false
      }
    }

    /**
     * Mengambil CSRF cookie kemudian melakukan login.
     */
    async function login(
      payload: LoginPayload,
    ): Promise<void> {
      loading.value = true

      try {
        await http.get('/sanctum/csrf-cookie')

        const response =
          await http.post<ApiResponse<AuthUser>>(
            '/api/v1/auth/login',
            payload,
          )

        user.value = response.data.data
        initialized.value = true
      } finally {
        loading.value = false
      }
    }

    /**
     * Menghapus session pada backend dan state frontend.
     */
    async function logout(): Promise<void> {
      loading.value = true

      try {
        await http.post('/api/v1/auth/logout')
      } finally {
        user.value = null
        initialized.value = true
        loading.value = false
      }
    }

    /**
     * Memeriksa role pengguna.
     */
    function hasRole(
      allowedRoles: UserRole[],
    ): boolean {
      if (!user.value) {
        return false
      }

      return allowedRoles.includes(user.value.role)
    }

    return {
      user,
      role,
      loading,
      initialized,
      isAuthenticated,
      fetchUser,
      login,
      logout,
      hasRole,
    }
  },
)