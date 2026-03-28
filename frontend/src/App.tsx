import { useEffect } from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './store/auth'
import Layout from './components/Layout'
import Login from './pages/Login'
import ForgotPassword from './pages/ForgotPassword'
import ResetPassword from './pages/ResetPassword'
import Dashboard from './pages/Dashboard'
import Contacts from './pages/Contacts'
import ContactDetail from './pages/ContactDetail'
import Accounts from './pages/Accounts'
import AccountDetail from './pages/AccountDetail'
import Deals from './pages/Deals'
import DealDetail from './pages/DealDetail'
import Activities from './pages/Activities'
import ActivityDetail from './pages/ActivityDetail'
import Users from './pages/Users'
import RolePermissions from './pages/RolePermissions'

function RequireAuth({ children }: { children: React.ReactNode }) {
  const token = useAuthStore((s) => s.token)
  return token ? <>{children}</> : <Navigate to="/login" replace />
}

export default function App() {
  const token = useAuthStore((s) => s.token)
  const user = useAuthStore((s) => s.user)
  const fetchMe = useAuthStore((s) => s.fetchMe)

  useEffect(() => {
    if (token && !user) fetchMe()
  }, [token])

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route
          path="/"
          element={
            <RequireAuth>
              <Layout />
            </RequireAuth>
          }
        >
          <Route index element={<Dashboard />} />
          <Route path="contacts" element={<Contacts />} />
          <Route path="contacts/:id" element={<ContactDetail />} />
          <Route path="accounts" element={<Accounts />} />
          <Route path="accounts/:id" element={<AccountDetail />} />
          <Route path="deals" element={<Deals />} />
          <Route path="deals/:id" element={<DealDetail />} />
          <Route path="activities" element={<Activities />} />
          <Route path="activities/:id" element={<ActivityDetail />} />
          <Route path="users" element={<Users />} />
          <Route path="role-permissions" element={<RolePermissions />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
