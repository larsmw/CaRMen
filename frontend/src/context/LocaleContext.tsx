import { createContext, useContext, useState, type ReactNode } from 'react'

const STORAGE_KEY = 'crm-locale'

export const LOCALES = [
  { value: 'en-US', label: 'English' },
  { value: 'da-DK', label: 'Dansk' },
  { value: 'fr-FR', label: 'Français' },
  { value: 'de-DE', label: 'Deutsch' },
  { value: 'es-ES', label: 'Español' },
  { value: 'nl-NL', label: 'Nederlands' },
  { value: 'sv-SE', label: 'Svenska' },
  { value: 'nb-NO', label: 'Norsk' },
]

interface LocaleContextValue {
  locale: string
  setLocale: (locale: string) => void
}

const LocaleContext = createContext<LocaleContextValue>({
  locale: 'en-US',
  setLocale: () => {},
})

export function LocaleProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState(
    () => localStorage.getItem(STORAGE_KEY) ?? navigator.language
  )

  const setLocale = (l: string) => {
    localStorage.setItem(STORAGE_KEY, l)
    setLocaleState(l)
  }

  return (
    <LocaleContext.Provider value={{ locale, setLocale }}>
      {children}
    </LocaleContext.Provider>
  )
}

export const useLocale = () => useContext(LocaleContext)
