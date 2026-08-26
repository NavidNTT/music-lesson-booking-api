import axios from 'axios';
import { env } from '@/shared/config/env';
import { tokenStorage } from './tokenStorage';
import { normalizeError } from './normalizeError';

/**
 * Called when the API reports 401 (token expired / invalid). The auth layer
 * registers a handler here to purge session state and redirect.
 */
let onUnauthenticated: (() => void) | null = null;

export function setUnauthenticatedHandler(handler: () => void): void {
  onUnauthenticated = handler;
}

export const apiClient = axios.create({
  baseURL: env.apiV1Url,
  timeout: 20000,
  headers: {
    Accept: 'application/json',
  },
});

apiClient.interceptors.request.use((config) => {
  const token = tokenStorage.get();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const apiError = normalizeError(error);
    const originalRequest = error.config;

    // Attempt silent token refresh once on 401 (skip auth endpoints)
    if (
      apiError.kind === 'unauthenticated' &&
      !originalRequest._retry &&
      !originalRequest.url?.includes('/auth/')
    ) {
      originalRequest._retry = true;
      const token = tokenStorage.get();
      if (token) {
        try {
          const refreshResponse = await axios.post(
            `${env.apiV1Url}/auth/refresh`,
            {},
            { headers: { Authorization: `Bearer ${token}` } },
          );
          const newToken = refreshResponse.data?.data?.token;
          if (newToken) {
            tokenStorage.set(newToken);
            originalRequest.headers.Authorization = `Bearer ${newToken}`;
            return apiClient(originalRequest);
          }
        } catch {
          // Refresh failed — fall through to hard logout
        }
      }
      tokenStorage.clear();
      onUnauthenticated?.();
      return Promise.reject(apiError);
    }

    if (apiError.kind === 'unauthenticated') {
      tokenStorage.clear();
      onUnauthenticated?.();
    }

    return Promise.reject(apiError);
  },
);
