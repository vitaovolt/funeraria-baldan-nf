import { useEffect, useRef, useState } from 'react'
import {
  getConfiguracaoFiscal,
  updateConfiguracaoFiscal,
  uploadCertificado,
} from '../api/dominio'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'

const inicial = {
  razao_social: '',
  nome_fantasia: '',
  cnpj: '',
  inscricao_estadual: '',
  regime_tributario: 'simples',
  ambiente_nfce: 'homologacao',
  serie_nfce: 1,
  proximo_numero_nfce: 1,
  uf: 'SP',
  municipio: '',
  codigo_ibge: '',
}

function maskCnpj(raw) {
  const d = String(raw || '').replace(/\D/g, '').slice(0, 14)
  return d
    .replace(/^(\d{2})(\d)/, '$1.$2')
    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1/$2')
    .replace(/(\d{4})(\d)/, '$1-$2')
}

function erroValidacao(err) {
  const errors = err.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat()[0]
    if (first) return String(first)
  }
  return err.response?.data?.message || 'Não foi possível salvar a configuração.'
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
    if (!form.cnpj.trim()) {
      toast.error('Informe o CNPJ — é o que a Focus usa na emissão.')
      return
    }
    saveRef.current = true
    setSaving(true)
    try {
      const res = await updateConfiguracaoFiscal({
        ...form,
        razao_social: form.razao_social || null,
        nome_fantasia: form.nome_fantasia || null,
        inscricao_estadual: form.inscricao_estadual || null,
        municipio: form.municipio || null,
        codigo_ibge: form.codigo_ibge || null,
        regime_tributario: form.regime_tributario || null,
        serie_nfce: Number(form.serie_nfce),
        proximo_numero_nfce: Number(form.proximo_numero_nfce),
      })
      toast.success('Configuração fiscal salva.')
      setTemCertificado(Boolean(res?.data?.tem_certificado))
    } catch (err) {
      toast.error(erroValidacao(err))
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

  return (
    <div data-testid="page-config">
      <form onSubmit={salvar}>
        <div className="page-head">
          <div>
            <h1>Configuração fiscal</h1>
            <p>
              A Focus NFe já tem empresa, certificado e CSC. Aqui o sistema só precisa do CNPJ, ambiente, série e
              próximo número para montar a NFC-e.
            </p>
          </div>
        </div>
        {!isAdmin ? (
          <p className="hint" data-testid="config-somente-leitura">
            Somente leitura. Alterações exigem perfil admin.
          </p>
        ) : null}

        <section className="panel">
          <h2 className="m-0 mb-2 text-lg font-bold text-[var(--brand-primary)]">Usado na emissão</h2>
          <p className="hint mt-0">Enviado à Focus em cada venda (CNPJ + série/número). Ambiente escolhe o token.</p>
          <div className="mt-3 grid gap-4 md:grid-cols-2">
            <div className="field" style={{ margin: 0 }}>
              <label>CNPJ</label>
              <input
                value={form.cnpj}
                onChange={(e) => campo('cnpj', maskCnpj(e.target.value))}
                disabled={!isAdmin}
                placeholder="00.000.000/0000-00"
              />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Ambiente NFC-e</label>
              <select value={form.ambiente_nfce} onChange={(e) => campo('ambiente_nfce', e.target.value)} disabled={!isAdmin}>
                <option value="homologacao">Homologação</option>
                <option value="producao">Produção</option>
              </select>
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Série NFC-e</label>
              <input type="number" min="1" step="1" value={form.serie_nfce} onChange={(e) => campo('serie_nfce', e.target.value)} disabled={!isAdmin} />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Próximo número</label>
              <input
                type="number"
                min="1"
                step="1"
                value={form.proximo_numero_nfce}
                onChange={(e) => campo('proximo_numero_nfce', e.target.value)}
                disabled={!isAdmin}
              />
            </div>
          </div>
        </section>

        <section className="panel mt-4">
          <h2 className="m-0 mb-2 text-lg font-bold text-[var(--brand-primary)]">Referência local (opcional)</h2>
          <p className="hint mt-0">Não vão na chamada da Focus — já estão no cadastro da empresa lá. Só para conferência aqui.</p>
          <div className="mt-3 grid gap-4 md:grid-cols-2">
            <div className="field" style={{ margin: 0 }}>
              <label>Razão social</label>
              <input value={form.razao_social} onChange={(e) => campo('razao_social', e.target.value)} disabled={!isAdmin} />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Nome fantasia</label>
              <input value={form.nome_fantasia} onChange={(e) => campo('nome_fantasia', e.target.value)} disabled={!isAdmin} />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Inscrição estadual</label>
              <input value={form.inscricao_estadual} onChange={(e) => campo('inscricao_estadual', e.target.value)} disabled={!isAdmin} />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Regime tributário</label>
              <select
                value={form.regime_tributario || ''}
                onChange={(e) => campo('regime_tributario', e.target.value)}
                disabled={!isAdmin}
              >
                <option value="simples">Simples Nacional</option>
                <option value="lucro_presumido">Lucro presumido</option>
                <option value="lucro_real">Lucro real</option>
              </select>
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Município</label>
              <input value={form.municipio} onChange={(e) => campo('municipio', e.target.value)} disabled={!isAdmin} />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>UF</label>
              <input value={form.uf} maxLength={2} onChange={(e) => campo('uf', e.target.value.toUpperCase())} disabled={!isAdmin} />
            </div>
            <div className="field" style={{ margin: 0 }}>
              <label>Código IBGE</label>
              <input
                value={form.codigo_ibge}
                onChange={(e) => campo('codigo_ibge', e.target.value.replace(/\D/g, '').slice(0, 7))}
                disabled={!isAdmin}
                placeholder="3517306"
              />
            </div>
          </div>
        </section>

        {isAdmin ? (
          <button className="btn btn-primary mt-4" disabled={saving} data-testid="config-salvar">
            {saving ? 'Processando…' : 'Salvar configuração'}
          </button>
        ) : null}
      </form>

      <section className="panel mt-6">
        <h2 className="m-0 text-lg font-extrabold">Certificado A1</h2>
        <p className="hint">
          A emissão usa o A1 cadastrado na Focus. O upload abaixo é só cópia local (opcional), não substitui o da Focus.
        </p>
        <p className="text-sm text-[var(--muted)]">
          {temCertificado ? 'Cópia local armazenada.' : 'Nenhuma cópia local.'}
        </p>
        {isAdmin ? (
          <div className="flex flex-wrap gap-3">
            <input type="file" accept=".pfx,.p12" onChange={(e) => setCertificado(e.target.files?.[0] || null)} />
            <button
              type="button"
              className="btn btn-accent"
              disabled={uploading}
              onClick={enviarCertificado}
              data-testid="certificado-upload"
            >
              {uploading ? 'Processando…' : 'Enviar cópia local'}
            </button>
          </div>
        ) : null}
      </section>
    </div>
  )
}
