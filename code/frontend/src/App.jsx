import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import AppShell from './components/layout/AppShell'
import ProtectedRoute from './components/layout/ProtectedRoute'
import { AuthProvider } from './context/AuthContext'
import { ToastProvider } from './context/ToastContext'
import CaixaPage from './pages/CaixaPage'
import ClienteFormPage from './pages/ClienteFormPage'
import ClientesPage from './pages/ClientesPage'
import ConfigPage from './pages/ConfigPage'
import ConsignadoPage from './pages/ConsignadoPage'
import EstoquePage from './pages/EstoquePage'
import HomePage from './pages/HomePage'
import LoginPage from './pages/LoginPage'
import MarcasCategoriasPage from './pages/MarcasCategoriasPage'
import NotasPage from './pages/NotasPage'
import PdvPage from './pages/PdvPage'
import ProdutoFormPage from './pages/ProdutoFormPage'
import ProdutosPage from './pages/ProdutosPage'
import UsuariosPage from './pages/UsuariosPage'

export default function App() {
  return (
    <AuthProvider>
      <ToastProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route
              element={(
                <ProtectedRoute>
                  <AppShell />
                </ProtectedRoute>
              )}
            >
              <Route path="/" element={<HomePage />} />
              <Route path="/caixa" element={<CaixaPage />} />
              <Route path="/pdv" element={<PdvPage />} />
              <Route path="/notas" element={<NotasPage />} />
              <Route path="/marcas-categorias" element={<MarcasCategoriasPage />} />
              <Route path="/produtos" element={<ProdutosPage />} />
              <Route path="/produtos/novo" element={<ProdutoFormPage />} />
              <Route path="/produtos/:id" element={<ProdutoFormPage />} />
              <Route path="/clientes" element={<ClientesPage />} />
              <Route path="/clientes/novo" element={<ClienteFormPage />} />
              <Route path="/clientes/:id" element={<ClienteFormPage />} />
              <Route path="/estoque" element={<EstoquePage />} />
              <Route path="/consignado" element={<ConsignadoPage />} />
              <Route path="/config" element={<ConfigPage />} />
              <Route path="/usuarios" element={<UsuariosPage />} />
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </BrowserRouter>
      </ToastProvider>
    </AuthProvider>
  )
}
