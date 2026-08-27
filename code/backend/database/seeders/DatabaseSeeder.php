<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ConfiguracaoFiscal;
use App\Models\Dependente;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'operador@baldan.local'],
            [
                'name' => 'Operador Baldan',
                'password' => Hash::make('password'),
                'ativo' => true,
                'role' => 'operador',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@baldan.local'],
            [
                'name' => 'Admin Baldan',
                'password' => Hash::make('password'),
                'ativo' => true,
                'role' => 'admin',
            ]
        );

        ConfiguracaoFiscal::query()->updateOrCreate(
            ['cnpj' => '12345678000199'],
            [
                'razao_social' => 'Funeraria Baldan Ltda',
                'nome_fantasia' => 'Baldan',
                'inscricao_estadual' => '123456789',
                'regime_tributario' => 'simples',
                'ambiente_nfce' => 'homologacao',
                'serie_nfce' => 1,
                'proximo_numero_nfce' => 1,
                'uf' => 'SP',
                'municipio' => 'Sao Paulo',
                'codigo_ibge' => '3550308',
            ]
        );

        $ortopedia = Marca::query()->updateOrCreate(['nome' => 'OrtoMais'], ['ativo' => true]);
        $flores = Marca::query()->updateOrCreate(['nome' => 'FlorArte'], ['ativo' => true]);
        $geral = Marca::query()->updateOrCreate(['nome' => 'Baldan'], ['ativo' => true]);

        $catOrto = Categoria::query()->updateOrCreate(['nome' => 'Ortopedia'], ['ativo' => true]);
        $catUrnas = Categoria::query()->updateOrCreate(['nome' => 'Urnas'], ['ativo' => true]);
        $catFlores = Categoria::query()->updateOrCreate(['nome' => 'Flores'], ['ativo' => true]);
        $catDiversos = Categoria::query()->updateOrCreate(['nome' => 'Diversos'], ['ativo' => true]);

        $produtos = [
            ['codigo_barras' => '7891000100011', 'descricao' => 'Muleta Axilar Aluminio', 'marca_id' => $ortopedia->id, 'categoria_id' => $catOrto->id, 'ncm' => '90211010', 'custo' => 45.00, 'preco_venda' => 89.90, 'estoque_atual' => 12],
            ['codigo_barras' => '7891000100028', 'descricao' => 'Cadeira de Rodas Basica', 'marca_id' => $ortopedia->id, 'categoria_id' => $catOrto->id, 'ncm' => '87131000', 'custo' => 380.00, 'preco_venda' => 699.00, 'estoque_atual' => 3],
            ['codigo_barras' => '7891000100035', 'descricao' => 'Urna Madeira Padrao', 'marca_id' => $geral->id, 'categoria_id' => $catUrnas->id, 'ncm' => '44219000', 'custo' => 220.00, 'preco_venda' => 450.00, 'estoque_atual' => 5],
            ['codigo_barras' => '7891000100042', 'descricao' => 'Coroa de Flores Media', 'marca_id' => $flores->id, 'categoria_id' => $catFlores->id, 'ncm' => '06039000', 'custo' => 80.00, 'preco_venda' => 160.00, 'estoque_atual' => 8],
            ['codigo_barras' => '7891000100059', 'descricao' => 'Vela 7 Dias', 'marca_id' => $geral->id, 'categoria_id' => $catDiversos->id, 'ncm' => '34060000', 'custo' => 4.50, 'preco_venda' => 12.00, 'estoque_atual' => 40],
            ['codigo_barras' => '7891000100066', 'descricao' => 'Andador Articulado', 'marca_id' => $ortopedia->id, 'categoria_id' => $catOrto->id, 'ncm' => '90211010', 'custo' => 110.00, 'preco_venda' => 219.90, 'estoque_atual' => 6],
            ['codigo_barras' => '7891000100073', 'descricao' => 'Buque Simples', 'marca_id' => $flores->id, 'categoria_id' => $catFlores->id, 'ncm' => '06039000', 'custo' => 25.00, 'preco_venda' => 55.00, 'estoque_atual' => 15],
            ['codigo_barras' => '7891000100080', 'descricao' => 'Urna Infantil', 'marca_id' => $geral->id, 'categoria_id' => $catUrnas->id, 'ncm' => '44219000', 'custo' => 150.00, 'preco_venda' => 320.00, 'estoque_atual' => 2],
        ];

        foreach ($produtos as $dados) {
            Produto::query()->updateOrCreate(
                ['codigo_barras' => $dados['codigo_barras']],
                array_merge($dados, ['referencia' => 'REF-'.substr($dados['codigo_barras'], -4), 'ativo' => true])
            );
        }

        $titularPlano = Cliente::query()->updateOrCreate(
            ['documento' => '12345678901'],
            [
                'tipo' => 'pf',
                'nome' => 'Maria Silva',
                'email' => 'maria@exemplo.local',
                'telefone' => '11999990001',
                'tem_plano' => true,
                'plano_nome' => 'Plano Familiar',
                'ativo' => true,
            ]
        );

        Dependente::query()->updateOrCreate(
            ['cliente_id' => $titularPlano->id, 'nome' => 'Joao Silva'],
            ['parentesco' => 'filho', 'documento' => '12345678902', 'ativo' => true]
        );
        Dependente::query()->updateOrCreate(
            ['cliente_id' => $titularPlano->id, 'nome' => 'Ana Silva'],
            ['parentesco' => 'filha', 'documento' => '12345678903', 'ativo' => true]
        );

        Cliente::query()->updateOrCreate(
            ['documento' => '98765432100'],
            [
                'tipo' => 'pf',
                'nome' => 'Carlos Souza',
                'email' => 'carlos@exemplo.local',
                'telefone' => '11988880002',
                'tem_plano' => false,
                'plano_nome' => null,
                'ativo' => true,
            ]
        );
    }
}
