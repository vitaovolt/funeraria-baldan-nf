import { useEffect, useRef, useState } from 'react'
import {
  getConfiguracaoFiscal,
  updateConfiguracaoFiscal,
  uploadCertificado,
} from '../api/dominio'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'

const inicial = {
  razao_social: '', nome_fantasia: '', cnpj: '', inscricao_estadual: '',
  regime_tributario: 'simples', ambiente_nfce: 'homologacao', serie_nfce: 1,
  proximo_numero_nfce: 1, uf: 'SP', municipio: '', codigo_ibge: '',
}

export default function ConfigPage() {
  const toast = useToast()
  const { user } = useAuth()
  const isAdmin = user?.role === 'admin'
  const saveRef = useRef(false)
  const uploadRef = useRef(false)
  const [form, setForm] = useState(inicial)
  const [certificado, setCertificado] = useState(null)
  const [temCertificado, setTemCertificado] = useState(false)
  const [saving, setSaving] = useState(false)
  const [uploading, setUploading] = useState(false)

  useEffect(() => {
    let ativo = true
    getConfiguracaoFiscal()
      .then((res) => {
        if (!ativo || !res.data) return
        setForm(Object.fromEntries(Object.keys(inicial).map((key) => [key, res.data[key] ?? inicial[key]])))
        setTemCertificado(Boolean(res.data.tem_certificado))
      })
      .catch(() => {
        if (ativo) toast.error('Não foi possível carregar a configuração fiscal.')
      })
    return () => {
      ativo = false
    }
  }, [toast])

  function campo(nome, valor) {
    setForm((atual) => ({ ...atual, [nome]: valor }))
  }

  async function salvar(event) {
    event.preventDefault()
    if (!isAdmin) {
      toast.error('Apenas admin pode alterar a configuração fiscal.')
      return
    }
    if (saveRef.current) return
    if (!form.razao_social.trim() || !form.cnpj.trim()) {
      toast.error('Informe razão social e CNPJ.')
      return
    }
    saveRef.current = true
    setSaving(true)
    try {
      const res = await updateConfiguracaoFiscal({
        ...form,
        serie_nfce: Number(form.serie_nfce),
        proximo_numero_nfce: Number(form.proximo_numero_nfce),
      })
      toast.success('Configuração fiscal salva.')
      setTemCertificado(Boolean(res?.data?.tem_certificado))
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível salvar a configuração.')
    } finally {
      saveRef.current = false
      setSaving(false)
    }
  }

  async function enviarCertificado() {
    if (!isAdmin) {
      toast.error('Apenas admin pode enviar o certificado A1.')
      return
    }
    if (uploadRef.current) return
    if (!certificado) return toast.error('Selecione o certificado A1.')
    uploadRef.current = true
    setUploading(true)
    const data = new FormData()
    data.append('certificado', certificado)
    try {
      await uploadCertificado(data)
      setTemCertificado(true)
      setCertificado(null)
      toast.success('Certificado A1 enviado.')
    } catch (err) {
      toast.error(err.response?.data?.message || 'Não foi possível enviar o certificado.')
    } finally {
      uploadRef.current = false
      setUploading(false)
    }
  }

  const inputClass = 'rounded-lg border border-[var(--line)] px-3 py-2'
  return (
    <div data-testid="page-config">
      <form onSubmit={salvar}>
        <h1 className="m-0 text-2xl font-extrabold text-[var(--brand-primary)]">Configuração fiscal</h1>
        {!isAdmin ? (
          <p className="mt-2 text-sm text-[var(--muted)]" data-testid="config-somente-leitura">
            Somente leitura. Alterações e certificado A1 exigem perfil admin.
          </p>
        ) : null}
        <div className="mt-5 grid gap-4 rounded-[10px] border border-[var(--line)] bg-white p-5 md:grid-cols-2">
          {[
            ['razao_social', 'Razão social'], ['nome_fantasia', 'Nome fantasia'], ['cnpj', 'CNPJ'],
            ['inscricao_estadual', 'Inscrição estadual'], ['regime_tributario', 'Regime tributário'],
            ['municipio', 'Município'], ['codigo_ibge', 'Código IBGE'], ['uf', 'UF'],
          ].map(([nome, label]) => (
            <label key={nome} className="grid gap-1 text-sm font-semibold">{label}
              <input
                className={inputClass}
                value={form[nome]}
                onChange={(e) => campo(nome, e.target.value)}
                readOnly={!isAdmin}
                disabled={!isAdmin}
              />
            </label>
          ))}
          <label className="grid gap-1 text-sm font-semibold">Ambiente NFC-e
            <select
              className={inputClass}
              value={form.ambiente_nfce}
              onChange={(e) => campo('ambiente_nfce', e.target.value)}
              disabled={!isAdmin}
            >
              <option value="homologacao">Homologação</option><option value="producao">Produção</option>
            </select>
          </label>
          <label className="grid gap-1 text-sm font-semibold">Série NFC-e
            <input className={inputClass} type="number" min="1" value={form.serie_nfce} onChange={(e) => campo('serie_nfce', e.target.value)} disabled={!isAdmin} />
          </label>
          <label className="grid gap-1 text-sm font-semibold">Próximo número
            <input className={inputClass} type="number" min="1" value={form.proximo_numero_nfce} onChange={(e) => campo('proximo_numero_nfce', e.target.value)} disabled={!isAdmin} />
          </label>
        </div>
        {isAdmin ? (
          <button className="mt-4 rounded-lg bg-[var(--brand-primary)] px-5 py-3 font-bold text-white disabled:opacity-60" disabled={saving} data-testid="config-salvar">
            {saving ? 'Processando…' : 'Salvar configuração'}
          </button>
        ) : null}
      </form>
      <section className="mt-7 rounded-[10px] border border-[var(--line)] bg-white p-5">
        <h2 className="m-0 text-lg font-extrabold">Certificado A1</h2>
        <p className="text-sm text-[var(--muted)]">{temCertificado ? 'Certificado armazenado.' : 'Nenhum certificado armazenado.'}</p>
        {isAdmin ? (
          <div className="flex flex-wrap gap-3">
            <input type="file" accept=".pfx,.p12" onChange={(e) => setCertificado(e.target.files?.[0] || null)} />
            <button type="button" className="rounded-lg bg-[var(--brand-accent)] px-4 py-2 font-bold disabled:opacity-60" disabled={uploading} onClick={enviarCertificado} data-testid="certificado-upload">
              {uploading ? 'Processando…' : 'Enviar certificado'}
            </button>
          </div>
        ) : null}
      </section>
    </div>
  )
}
