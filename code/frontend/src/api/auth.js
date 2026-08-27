import client from './client'

export async function login(email, password, deviceName = 'desktop') {
  const { data } = await client.post('/auth/login', {
    email,
    password,
    device_name: deviceName,
  })
  return data
}

export async function logout() {
  const { data } = await client.post('/auth/logout')
  return data
}

export async function fetchMe() {
  const { data } = await client.get('/auth/me')
  return data
}

export async function refreshToken() {
  const { data } = await client.post('/auth/refresh')
  return data
}
