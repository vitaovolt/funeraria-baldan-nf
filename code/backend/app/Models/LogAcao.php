<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAcao extends Model
{
    public const UPDATED_AT = null;

    public const ACOES = [
        'auth.login' => 'Login',
        'auth.login_falhou' => 'Login falhou',
        'auth.logout' => 'Logout',
        'caixa.abrir' => 'Abriu o caixa',
        'caixa.fechar' => 'Fechou o caixa',
        'caixa.sangria' => 'Sangria',
        'caixa.suprimento' => 'Suprimento',
        'venda.finalizar' => 'Finalizou venda',
        'venda.emitir_nfce' => 'Emitiu NFC-e',
        'venda.emitir_nfe' => 'Emitiu NF-e',
        'nota.reemitir' => 'Reemitiu nota',
        'produto.criar' => 'Criou produto',
        'produto.atualizar' => 'Atualizou produto',
        'produto.excluir' => 'Excluiu produto',
        'cliente.criar' => 'Criou cliente',
        'cliente.atualizar' => 'Atualizou cliente',
        'cliente.excluir' => 'Excluiu cliente',
        'dependente.criar' => 'Criou dependente',
        'dependente.atualizar' => 'Atualizou dependente',
        'dependente.excluir' => 'Excluiu dependente',
        'marca.criar' => 'Criou marca',
        'marca.atualizar' => 'Atualizou marca',
        'marca.excluir' => 'Excluiu marca',
        'categoria.criar' => 'Criou categoria',
        'categoria.atualizar' => 'Atualizou categoria',
        'categoria.excluir' => 'Excluiu categoria',
        'usuario.criar' => 'Criou usuário',
        'usuario.atualizar' => 'Atualizou usuário',
        'usuario.excluir' => 'Excluiu usuário',
        'estoque.movimentar' => 'Movimentou estoque',
        'consignado.criar' => 'Criou consignado',
        'consignado.devolver' => 'Devolveu consignado',
        'consignado.converter' => 'Converteu consignado em venda',
        'config.atualizar' => 'Atualizou configuração',
        'config.certificado' => 'Enviou certificado A1',
    ];

    protected $table = 'logs_acoes';

    protected $fillable = [
        'user_id',
        'user_nome',
        'user_login',
        'acao',
        'recurso_tipo',
        'recurso_id',
        'descricao',
        'payload',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $appends = [
        'acao_label',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAcaoLabelAttribute(): string
    {
        return self::ACOES[$this->acao] ?? $this->acao;
    }

    public function scopeBusca(Builder $query, ?string $termo): Builder
    {
        if ($termo === null || trim($termo) === '') {
            return $query;
        }

        $termo = trim($termo);

        return $query->where(function (Builder $q) use ($termo) {
            $q->where('descricao', 'ilike', '%'.$termo.'%')
                ->orWhere('acao', 'ilike', '%'.$termo.'%')
                ->orWhere('user_nome', 'ilike', '%'.$termo.'%')
                ->orWhere('user_login', 'ilike', '%'.$termo.'%');
        });
    }
}
