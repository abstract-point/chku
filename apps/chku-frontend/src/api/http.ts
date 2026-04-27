import axios, { AxiosError } from 'axios'

export type ApiValidationErrors = Record<string, string[]>

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status?: number,
    public readonly validationErrors?: ApiValidationErrors,
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

export const http = axios.create({
  baseURL: '/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

export async function fetchCsrfCookie(): Promise<void> {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

http.interceptors.response.use(
  (response) => {
    if (
      response.data &&
      typeof response.data === 'object' &&
      'data' in response.data &&
      Object.keys(response.data).every((key) => ['data', 'links', 'meta'].includes(key))
    ) {
      return response.data.data
    }

    return response.data
  },
  (error: AxiosError<{ message?: string; errors?: ApiValidationErrors }>) => {
    const status = error.response?.status
    const message = error.response?.data?.message ?? error.message

    return Promise.reject(new ApiError(message, status, error.response?.data?.errors))
  },
)
