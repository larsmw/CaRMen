import { useState } from 'react'
import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store/auth'
import styles from './Layout.module.scss'

const navItems = [
  { to: '/',           label: 'Dashboard' },
  { to: '/contacts',   label: 'Contacts'  },
  { to: '/accounts',   label: 'Accounts'  },
  { to: '/deals',      label: 'Deals'     },
  { to: '/activities', label: 'Activities'},
]

const adminNavItems = [
  { to: '/users',            label: 'Users'            },
  { to: '/role-permissions', label: 'Role Permissions' },
]

export default function Layout() {
  const logout = useAuthStore((s) => s.logout)
  const user = useAuthStore((s) => s.user)
  const navigate = useNavigate()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN') ?? false
  const [menuOpen, setMenuOpen] = useState(false)

  const handleLogout = () => { logout(); navigate('/login') }
  const closeMenu = () => setMenuOpen(false)

  return (
    <div className={styles.shell}>
      {/* Mobile top bar */}
      <div className={styles.topBar}>
        <span className={styles.topBarLogo}>CRM</span>
        <button className={styles.hamburger} onClick={() => setMenuOpen(o => !o)} aria-label="Toggle menu">
          ☰
        </button>
      </div>

      {/* Overlay (mobile only) */}
      <div
        className={`${styles.overlay} ${menuOpen ? styles.overlayVisible : ''}`}
        onClick={closeMenu}
      />

      <aside className={`${styles.sidebar} ${menuOpen ? styles.sidebarOpen : ''}`}>
        <div className={styles.logo}>CRM</div>
        <nav className={styles.nav}>
          {navItems.map(({ to, label }) => (
            <NavLink
              key={to}
              to={to}
              end={to === '/'}
              className={({ isActive }) => `${styles.link} ${isActive ? styles.active : ''}`}
              onClick={closeMenu}
            >
              {label}
            </NavLink>
          ))}
          {isAdmin && (
            <>
              <div className={styles.adminLabel}>Admin</div>
              {adminNavItems.map(({ to, label }) => (
                <NavLink
                  key={to}
                  to={to}
                  className={({ isActive }) => `${styles.link} ${isActive ? styles.active : ''}`}
                  onClick={closeMenu}
                >
                  {label}
                </NavLink>
              ))}
            </>
          )}
        </nav>
        <div className={styles.userInfo}>
          {user?.fullName}
        </div>
        <button className={styles.logout} onClick={handleLogout}>Sign out</button>
      </aside>
      <main className={styles.main}>
        <Outlet />
      </main>
    </div>
  )
}
