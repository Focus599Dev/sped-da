<?php

namespace NFePHP\DA\NFCom;

use NFePHP\DA\Legacy\Common;
use NFePHP\DA\Legacy\Pdf;
use Exception;
use DateTime;

class DanfeCom extends Common
{
    protected $xml;
    protected $logo;
    protected $pdf;
    protected $dom;
    protected $fontePadrao = 'Helvetica';

    // Formatted details
    protected $emitNome;
    protected $emitCnpj;
    protected $emitIe;
    protected $emitLogradouro;
    protected $emitNumero;
    protected $emitBairro;
    protected $emitMunicipio;
    protected $emitUf;
    protected $emitCep;

    protected $destNome;
    protected $destCnpj;
    protected $destCpf;
    protected $destIdOutros;
    protected $destIe;
    protected $destLogradouro;
    protected $destNumero;
    protected $destBairro;
    protected $destMunicipio;
    protected $destUf;
    protected $destCep;

    protected $idCodAssinante;
    protected $nroTermPrinc;

    protected $competFat;
    protected $dVencFat;
    protected $codBarras;
    protected $urlPix;

    protected $nNF;
    protected $serie;
    protected $dhEmi;
    protected $tpAmb;
    protected $chave;

    protected $nProt;
    protected $dhRecbto;

    protected $totalVProd;
    protected $totalVBC;
    protected $totalVICMS;
    protected $totalVFCP;
    protected $totalVPIS;
    protected $totalVCOFINS;
    protected $totalVFUST;
    protected $totalVFUNTTEL;
    protected $totalVNF;
    protected $totalVDesc;
    protected $totalVOutro;

    protected $infCpl;
    protected $infAdFisco;
    protected $qrCodNFCom;

    // Layout configuration
    protected $orientacao = 'P';
    protected $papel = 'A4';
    protected $wPrint = 198;
    protected $hPrint = 285;

    public function __construct($xmlString = '', $logoPath = '')
    {
        $this->xml = $xmlString;
        $this->logo = $logoPath;

        if (empty($this->logo)) {
            $defaultLogo = __DIR__ . '/logo_nfcom.png';
            if (file_exists($defaultLogo)) {
                $this->logo = $defaultLogo;
            }
        }

        if (!empty($this->xml)) {
            $this->dom = new \DOMDocument();
            // Evita erros de parse com XMLs mal formados ou caracteres especiais
            $this->dom->loadXML($this->xml);
            $this->parseXml();
        }
    }

    protected function parseXml()
    {
        // Emitente
        $emitNode = $this->dom->getElementsByTagName('emit')->item(0);
        if ($emitNode) {
            $this->emitNome = $this->pSimpleGetValue($emitNode, 'xNome');
            $this->emitCnpj = $this->pSimpleGetValue($emitNode, 'CNPJ');
            $this->emitIe = $this->pSimpleGetValue($emitNode, 'IE');
            
            $enderEmit = $emitNode->getElementsByTagName('enderEmit')->item(0);
            if ($enderEmit) {
                $this->emitLogradouro = $this->pSimpleGetValue($enderEmit, 'xLgr');
                $this->emitNumero = $this->pSimpleGetValue($enderEmit, 'nro');
                $this->emitBairro = $this->pSimpleGetValue($enderEmit, 'xBairro');
                $this->emitMunicipio = $this->pSimpleGetValue($enderEmit, 'xMun');
                $this->emitUf = $this->pSimpleGetValue($enderEmit, 'UF');
                $this->emitCep = $this->pSimpleGetValue($enderEmit, 'CEP');
            }
        }

        // Destinatario
        $destNode = $this->dom->getElementsByTagName('dest')->item(0);
        if ($destNode) {
            $this->destNome = $this->pSimpleGetValue($destNode, 'xNome');
            $this->destCnpj = $this->pSimpleGetValue($destNode, 'CNPJ');
            $this->destCpf = $this->pSimpleGetValue($destNode, 'CPF');
            $this->destIdOutros = $this->pSimpleGetValue($destNode, 'idOutros');
            $this->destIe = $this->pSimpleGetValue($destNode, 'IE');

            $enderDest = $destNode->getElementsByTagName('enderDest')->item(0);
            if ($enderDest) {
                $this->destLogradouro = $this->pSimpleGetValue($enderDest, 'xLgr');
                $this->destNumero = $this->pSimpleGetValue($enderDest, 'nro');
                $this->destBairro = $this->pSimpleGetValue($enderDest, 'xBairro');
                $this->destMunicipio = $this->pSimpleGetValue($enderDest, 'xMun');
                $this->destUf = $this->pSimpleGetValue($enderDest, 'UF');
                $this->destCep = $this->pSimpleGetValue($enderDest, 'CEP');
            }
        }

        // Assinante
        $assinanteNode = $this->dom->getElementsByTagName('assinante')->item(0);
        if ($assinanteNode) {
            $this->idCodAssinante = $this->pSimpleGetValue($assinanteNode, 'iCodAssinante');
            $this->nroTermPrinc = $this->pSimpleGetValue($assinanteNode, 'NroTermPrinc');
        }

        // Fatura / gFat
        $gFatNode = $this->dom->getElementsByTagName('gFat')->item(0);
        if ($gFatNode) {
            $this->competFat = $this->pSimpleGetValue($gFatNode, 'CompetFat');
            $this->dVencFat = $this->pSimpleGetValue($gFatNode, 'dVencFat');
            $this->codBarras = $this->pSimpleGetValue($gFatNode, 'codBarras');
            
            $gPixNode = $gFatNode->getElementsByTagName('gPIX')->item(0);
            if ($gPixNode) {
                $this->urlPix = $this->pSimpleGetValue($gPixNode, 'urlQRCodePIX');
            }
        }

        // Ide
        $ideNode = $this->dom->getElementsByTagName('ide')->item(0);
        if ($ideNode) {
            $this->nNF = $this->pSimpleGetValue($ideNode, 'nNF');
            $this->serie = $this->pSimpleGetValue($ideNode, 'serie');
            $this->dhEmi = $this->pSimpleGetValue($ideNode, 'dhEmi');
            $this->tpAmb = $this->pSimpleGetValue($ideNode, 'tpAmb');
        }

        // InfProt
        $infProtNode = $this->dom->getElementsByTagName('infProt')->item(0);
        if ($infProtNode) {
            $this->nProt = $this->pSimpleGetValue($infProtNode, 'nProt');
            $this->dhRecbto = $this->pSimpleGetValue($infProtNode, 'dhRecbto');
            $this->chave = $this->pSimpleGetValue($infProtNode, 'chNFCom');
        }

        if (empty($this->chave)) {
            $infNFComNode = $this->dom->getElementsByTagName('infNFCom')->item(0);
            if ($infNFComNode && $infNFComNode->hasAttribute('Id')) {
                $this->chave = substr($infNFComNode->getAttribute('Id'), 5);
            }
        }

        // Totais
        $totalNode = $this->dom->getElementsByTagName('total')->item(0);
        if ($totalNode) {
            $this->totalVProd = (float) $this->pSimpleGetValue($totalNode, 'vProd');
            $this->totalVPIS = (float) $this->pSimpleGetValue($totalNode, 'vPIS');
            $this->totalVCOFINS = (float) $this->pSimpleGetValue($totalNode, 'vCOFINS');
            $this->totalVFUST = (float) $this->pSimpleGetValue($totalNode, 'vFUST');
            $this->totalVFUNTTEL = (float) $this->pSimpleGetValue($totalNode, 'vFUNTTEL');
            $this->totalVNF = (float) $this->pSimpleGetValue($totalNode, 'vNF');
            $this->totalVDesc = (float) $this->pSimpleGetValue($totalNode, 'vDesc');
            $this->totalVOutro = (float) $this->pSimpleGetValue($totalNode, 'vOutro');

            $icmsTotNode = $totalNode->getElementsByTagName('ICMSTot')->item(0);
            if ($icmsTotNode) {
                $this->totalVBC = (float) $this->pSimpleGetValue($icmsTotNode, 'vBC');
                $this->totalVICMS = (float) $this->pSimpleGetValue($icmsTotNode, 'vICMS');
                $this->totalVFCP = (float) $this->pSimpleGetValue($icmsTotNode, 'vFCP');
            }
        }

        // InfAdic
        $infAdicNode = $this->dom->getElementsByTagName('infAdic')->item(0);
        if ($infAdicNode) {
            $this->infCpl = $this->pSimpleGetValue($infAdicNode, 'infCpl');
            $this->infAdFisco = $this->pSimpleGetValue($infAdicNode, 'infAdFisco');
        }

        // Suplementares
        $suplNode = $this->dom->getElementsByTagName('infNFComSupl')->item(0);
        if ($suplNode) {
            $this->qrCodNFCom = $this->pSimpleGetValue($suplNode, 'qrCodNFCom');
        }
    }

    public function monta($orientacao = 'P', $papel = 'A4', $logoAlign = 'C')
    {
        $this->orientacao = $orientacao;
        $this->papel = $papel;

        // Instancia FPDF wrapper
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        $this->pdf->aliasNbPages();
        $this->pdf->AddPage($this->orientacao, $this->papel);

        $y = 6;
        $this->drawHeader($y);
        $y += 28;

        $this->drawDestinatario($y);
        $y += 32;

        $this->drawWarnings($y);
        $y += 14;

        $this->drawItems($y);
    }

    protected function drawHeader($y)
    {
        $x = 6;
        $aFontPadrao = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');

        // Divisão I: Emitente & Logo
        // Caixa do Logo
        $this->pTextBox($x, $y, 40, 26, '', array('font' => $this->fontePadrao, 'size' => 8, 'style' => ''), 'C', 'C', 1);
        if (!empty($this->logo) && file_exists($this->logo)) {
            // Desenha logo centralizado dentro da caixa de 40x26
            $this->pdf->Image($this->logo, $x + 2, $y + 2, 36, 22);
        } else {
            $this->pTextBox($x, $y, 40, 26, "LOGO\nEMPRESA", array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'), 'C', 'C', 0);
        }

        // Caixa do Emitente
        $emitAddr = $this->emitLogradouro . ', ' . $this->emitNumero . "\n" .
                    $this->emitBairro . ' - ' . $this->emitMunicipio . '/' . $this->emitUf . "\n" .
                    "CEP: " . $this->pFormat($this->emitCep, '#####-###') . "\n" .
                    "CNPJ: " . $this->pFormat($this->emitCnpj, '##.###.###/####-##') . "  IE: " . $this->emitIe;

        $this->pTextBox($x + 40, $y, 80, 26, '', $aFontPadrao, 'T', 'L', 1);
        $this->pTextBox($x + 42, $y + 1, 76, 5, $this->emitNome, array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B'), 'T', 'L', 0);
        $this->pTextBox($x + 42, $y + 6, 76, 15, $emitAddr, array('font' => $this->fontePadrao, 'size' => 8, 'style' => ''), 'T', 'L', 0);
        $this->pTextBox($x + 42, $y + 20, 76, 5, "DOCUMENTO AUXILIAR DA NF-COM", array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'), 'B', 'L', 0);

        // Caixa de Identificacao / Chave de Acesso (Direita)
        $this->pTextBox($x + 120, $y, 78, 26, '', $aFontPadrao, 'T', 'L', 1);

        // Desenha QR Code se existir
        if (!empty($this->qrCodNFCom)) {
            $nameTMP = 'qr_' . $this->chave . '.png';
            $folder = __DIR__ . '/../Legacy/QRCode/tmp/';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            $qrPath = $folder . $nameTMP;
            
            require_once __DIR__ . '/../Legacy/QRCode/qrlib.php';
            \QRCode::png(htmlspecialchars_decode($this->qrCodNFCom), $qrPath, 'M', 4, 1);
            
            if (file_exists($qrPath)) {
                $this->pdf->Image($qrPath, $x + 121, $y + 2, 22, 22, 'png');
                @unlink($qrPath);
            }
        }

        // Textos da Chave
        $numSer = "NF-COM Nº: " . $this->nNF . "  SÉRIE: " . $this->serie;
        $emiDt = "Emissão: " . ($this->dhEmi ? $this->pYmd2dmy(substr($this->dhEmi, 0, 10)) : '');
        $chaveFmt = "CHAVE DE ACESSO:\n" . $this->pFormat($this->chave, '#### #### #### #### #### #### #### #### #### #### ####');

        $this->pTextBox($x + 144, $y + 1, 53, 4, $numSer, array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'), 'T', 'L', 0);
        $this->pTextBox($x + 144, $y + 5, 53, 4, $emiDt, array('font' => $this->fontePadrao, 'size' => 7, 'style' => ''), 'T', 'L', 0);
        $this->pTextBox($x + 144, $y + 9, 53, 10, $chaveFmt, array('font' => $this->fontePadrao, 'size' => 6, 'style' => ''), 'T', 'L', 0);

        // Protocolo
        if ($this->tpAmb == '2') {
            $protoText = "HOMOLOGAÇÃO - SEM VALOR FISCAL";
        } else {
            $protoText = "PROTOCOLO: " . $this->nProt;
        }
        $this->pTextBox($x + 144, $y + 19, 53, 6, $protoText, array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'), 'B', 'L', 0);
    }

    protected function drawDestinatario($y)
    {
        $x = 6;
        $aFontPadrao = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');

        // Divisão II: Informações do Destinatário & Fatura
        // Bloco Destinatario
        $this->pTextBox($x, $y, 120, 30, '', $aFontPadrao, 'T', 'L', 1);
        $this->pTextBox($x + 2, $y + 1, 116, 4, "DESTINATÁRIO / ASSINANTE", array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'), 'T', 'L', 0);

        $destCnpjCpf = '';
        if (!empty($this->destCnpj)) {
            $destCnpjCpf = "CNPJ: " . $this->pFormat($this->destCnpj, '##.###.###/####-##');
        } elseif (!empty($this->destCpf)) {
            $destCnpjCpf = "CPF: " . $this->pFormat($this->destCpf, '###.###.###-##');
        } elseif (!empty($this->destIdOutros)) {
            $destCnpjCpf = "Outros: " . $this->destIdOutros;
        }

        $destAddr = $this->destLogradouro . ', ' . $this->destNumero . ' - ' . $this->destBairro . ' - ' . $this->destMunicipio . '/' . $this->destUf . ' - CEP: ' . $this->pFormat($this->destCep, '#####-###');

        $this->pTextBox($x + 2, $y + 5, 116, 4, "Nome: " . $this->destNome, array('font' => $this->fontePadrao, 'size' => 8, 'style' => ''), 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 9, 116, 4, $destCnpjCpf . "   IE: " . $this->destIe, array('font' => $this->fontePadrao, 'size' => 8, 'style' => ''), 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 13, 116, 8, "Endereço: " . $destAddr, array('font' => $this->fontePadrao, 'size' => 8, 'style' => ''), 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 21, 116, 4, "Nro. Cliente: " . $this->idCodAssinante . "   Telefone: " . $this->nroTermPrinc, array('font' => $this->fontePadrao, 'size' => 8, 'style' => ''), 'T', 'L', 0);

        // Bloco Fatura (Referência / Vencimento / Total)
        $this->pdf->SetFillColor(230, 240, 250);
        
        $compet = '';
        if (!empty($this->competFat)) {
            $compet = substr($this->competFat, 4, 2) . '/' . substr($this->competFat, 0, 4);
        }

        $venc = '';
        if (!empty($this->dVencFat)) {
            $venc = $this->pYmd2dmy($this->dVencFat);
        }

        $this->pTextBox($x + 120, $y, 78, 10, "COMPETÊNCIA / REFERÊNCIA\n" . $compet, array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'), 'C', 'C', 1, '', true);
        $this->pTextBox($x + 120, $y + 10, 78, 10, "VENCIMENTO\n" . $venc, array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'), 'C', 'C', 1, '', true);
        $this->pTextBox($x + 120, $y + 20, 78, 10, "TOTAL A PAGAR\nR$ " . number_format($this->totalVNF, 2, ',', '.'), array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'), 'C', 'C', 1, '', true);
    }

    protected function drawWarnings($y)
    {
        $x = 6;

        // Divisão VI: Mensagem Fiscal
        $msg = "MENSAGENS PRIORITÁRIAS / AVISOS AO CONSUMIDOR\n";
        if ($this->tpAmb == '2') {
            $msg .= "EMITIDO EM AMBIENTE DE HOMOLOGAÇÃO – SEM VALOR FISCAL\n";
        }
        if (empty($this->nProt)) {
            $msg .= "EMITIDO EM CONTINGÊNCIA - Pendente de autorização\n";
        }
        if (!empty($this->infAdFisco)) {
            $msg .= $this->infAdFisco;
        }

        $this->pTextBox($x, $y, 198, 12, trim($msg), array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'), 'C', 'C', 1);
    }

    protected function drawItems($y)
    {
        $this->drawTableHeader($y);
        $y += 6;

        // Loop sobre os itens
        $detList = $this->dom->getElementsByTagName('det');
        $itemCount = $detList->length;

        for ($i = 0; $i < $itemCount; $i++) {
            $det = $detList->item($i);
            $prod = $det->getElementsByTagName('prod')->item(0);
            
            if (!$prod) {
                continue;
            }

            $xProd = $this->pSimpleGetValue($prod, 'xProd');
            $indDev = $this->pSimpleGetValue($prod, 'indDevolucao');
            if ($indDev == '1') {
                $xProd .= ' (Devolução)';
            }

            $uMed = $this->pSimpleGetValue($prod, 'uMed');
            $qFaturada = (float) $this->pSimpleGetValue($prod, 'qFaturada');
            $vItem = (float) $this->pSimpleGetValue($prod, 'vItem');
            $vProd = (float) $this->pSimpleGetValue($prod, 'vProd');

            // Imposto
            $vPisCofins = 0.0;
            $vBC = 0.0;
            $pICMS = 0.0;
            $vICMS = 0.0;

            $imposto = $det->getElementsByTagName('imposto')->item(0);
            if ($imposto) {
                // PIS e COFINS
                $vPISVal = (float) $this->pSimpleGetValue($imposto, 'vPIS');
                $vCOFINSVal = (float) $this->pSimpleGetValue($imposto, 'vCOFINS');
                $vPisCofins = $vPISVal + $vCOFINSVal;

                // Busca tag de ICMS
                $icmsTags = array('ICMS00', 'ICMS20', 'ICMS45', 'ICMS90');
                foreach ($icmsTags as $tag) {
                    $icmsNode = $imposto->getElementsByTagName($tag)->item(0);
                    if ($icmsNode) {
                        $vBC = (float) $this->pSimpleGetValue($icmsNode, 'vBC');
                        $pICMS = (float) $this->pSimpleGetValue($icmsNode, 'pICMS');
                        $vICMS = (float) $this->pSimpleGetValue($icmsNode, 'vICMS');
                        
                        // Soma FCP se houver
                        $vFCPVal = (float) $this->pSimpleGetValue($icmsNode, 'vFCP');
                        $pFCPVal = (float) $this->pSimpleGetValue($icmsNode, 'pFCP');
                        $vICMS += $vFCPVal;
                        $pICMS += $pFCPVal;
                        break;
                    }
                }
            }

            // Calcula quantidade de linhas necessária
            $numLines = $this->pGetNumLines($xProd, 70, array('font' => $this->fontePadrao, 'size' => 7, 'style' => ''));
            $rowHeight = max(5, $numLines * 3.5);

            // Verifica quebra de página (A4 tem ~285mm de altura util, queremos quebrar antes de estourar a margem)
            if ($y + $rowHeight > 230) {
                $this->pdf->AddPage($this->orientacao, $this->papel);
                $y = 6;
                $this->drawTableHeader($y);
                $y += 6;
            }

            $this->drawTableRow($y, $rowHeight, $xProd, $uMed, $qFaturada, $vItem, $vProd, $vPisCofins, $vBC, $pICMS, $vICMS);
            $y += $rowHeight;
        }

        // Adiciona linha de totalização ao final da tabela
        if ($y + 6 > 230) {
            $this->pdf->AddPage($this->orientacao, $this->papel);
            $y = 6;
            $this->drawTableHeader($y);
            $y += 6;
        }

        // Desenha linha de Total
        $this->drawTableTotalRow($y);
        $y += 6 + 4; // avança Y com margem

        // Desenha rodapé com tributos, pix, código de barras
        if ($y + 40 > 280) {
            $this->pdf->AddPage($this->orientacao, $this->papel);
            $y = 6;
        }

        $this->drawFooterBlocks($y);
    }

    protected function drawTableHeader($y)
    {
        $x = 6;
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        
        $this->pTextBox($x, $y, 70, 6, "ITENS DA FATURA", $aFont, 'C', 'L', 1);
        $this->pTextBox($x + 70, $y, 10, 6, "UN", $aFont, 'C', 'C', 1);
        $this->pTextBox($x + 80, $y, 15, 6, "QUANT", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 95, $y, 20, 6, "PREÇO UNIT", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 115, $y, 22, 6, "VALOR TOTAL", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 137, $y, 20, 6, "PIS/COFINS", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 157, $y, 17, 6, "BC ICMS", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 174, $y, 10, 6, "ALÍQ", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 184, $y, 14, 6, "VALOR ICMS", $aFont, 'C', 'R', 1);
    }

    protected function drawTableRow($y, $h, $xProd, $uMed, $qFaturada, $vItem, $vProd, $vPisCofins, $vBC, $pICMS, $vICMS)
    {
        $x = 6;
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');

        $this->pTextBox($x, $y, 70, $h, $xProd, $aFont, 'C', 'L', 1);
        $this->pTextBox($x + 70, $y, 10, $h, $uMed, $aFont, 'C', 'C', 1);
        $this->pTextBox($x + 80, $y, 15, $h, number_format($qFaturada, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 95, $y, 20, $h, number_format($vItem, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 115, $y, 22, $h, number_format($vProd, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 137, $y, 20, $h, number_format($vPisCofins, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 157, $y, 17, $h, number_format($vBC, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 174, $y, 10, $h, number_format($pICMS, 2, ',', '.') . '%', $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 184, $y, 14, $h, number_format($vICMS, 2, ',', '.'), $aFont, 'C', 'R', 1);
    }

    protected function drawTableTotalRow($y)
    {
        $x = 6;
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        
        $this->pTextBox($x, $y, 70, 6, "TOTAL", $aFont, 'C', 'L', 1);
        $this->pTextBox($x + 70, $y, 10, 6, "", $aFont, 'C', 'C', 1);
        $this->pTextBox($x + 80, $y, 15, 6, "", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 95, $y, 20, 6, "", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 115, $y, 22, 6, number_format($this->totalVProd, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 137, $y, 20, 6, number_format($this->totalVPIS + $this->totalVCOFINS, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 157, $y, 17, 6, number_format($this->totalVBC, 2, ',', '.'), $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 174, $y, 10, 6, "", $aFont, 'C', 'R', 1);
        $this->pTextBox($x + 184, $y, 14, 6, number_format($this->totalVICMS, 2, ',', '.'), $aFont, 'C', 'R', 1);
    }

    protected function drawFooterBlocks($y)
    {
        $x = 6;
        $aFontB = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');
        $aFontPadrao = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');

        // 1. Box Valores (Esquerda)
        $this->pTextBox($x, $y, 60, 24, '', $aFontPadrao, 'T', 'L', 1);
        $this->pTextBox($x + 2, $y + 1, 56, 4, "RESUMO DE VALORES", $aFontB, 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 5, 56, 4, "Valor Total NFF: R$ " . number_format($this->totalVNF, 2, ',', '.'), $aFont, 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 9, 56, 4, "Base Calc. ICMS: R$ " . number_format($this->totalVBC, 2, ',', '.'), $aFont, 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 13, 56, 4, "Valor ICMS: R$ " . number_format($this->totalVICMS, 2, ',', '.'), $aFont, 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 17, 56, 4, "Descontos: R$ " . number_format($this->totalVDesc, 2, ',', '.') . "  Outros: R$ " . number_format($this->totalVOutro, 2, ',', '.'), $aFont, 'T', 'L', 0);

        // 2. Box Tributos (Meio)
        $this->pTextBox($x + 60, $y, 68, 24, '', $aFontPadrao, 'T', 'L', 1);
        $this->pTextBox($x + 62, $y + 1, 64, 4, "OUTROS TRIBUTOS", $aFontB, 'T', 'L', 0);
        $this->pTextBox($x + 62, $y + 5, 64, 4, "PIS: R$ " . number_format($this->totalVPIS, 2, ',', '.') . "  COFINS: R$ " . number_format($this->totalVCOFINS, 2, ',', '.'), $aFont, 'T', 'L', 0);
        $this->pTextBox($x + 62, $y + 9, 64, 4, "FUST: R$ " . number_format($this->totalVFUST, 2, ',', '.'), $aFont, 'T', 'L', 0);
        $this->pTextBox($x + 62, $y + 13, 64, 4, "FUNTTEL: R$ " . number_format($this->totalVFUNTTEL, 2, ',', '.'), $aFont, 'T', 'L', 0);

        // 3. Reservado ao Fisco (Direita)
        $fiscoText = "RESERVADO AO FISCO\n";
        if (empty($this->nProt)) {
            $fiscoText .= "EMITIDO EM CONTINGÊNCIA\nPendente de autorização";
        }
        $this->pTextBox($x + 128, $y, 70, 24, trim($fiscoText), $aFontB, 'C', 'C', 1);

        $y += 26;

        // 4. Informações Complementares
        $cplText = "INFORMAÇÕES COMPLEMENTARES\n" . $this->infCpl;
        $this->pTextBox($x, $y, 198, 14, trim($cplText), $aFont, 'T', 'L', 1);

        $y += 16;

        // 5. Área de Pagamento / ANATEL / PIX
        // Caixa do Débito / ANATEL
        $this->pTextBox($x, $y, 70, 20, '', $aFontPadrao, 'T', 'L', 1);
        $this->pTextBox($x + 2, $y + 1, 66, 4, "ÁREA DO CONTRIBUINTE / ANATEL", $aFontB, 'T', 'L', 0);
        $this->pTextBox($x + 2, $y + 5, 66, 14, "Determinações da ANATEL: Contribuímos com o FUST e FUNTTEL sem repasse ao consumidor.", $aFont, 'T', 'L', 0);

        // Código de Barras
        $this->pTextBox($x + 70, $y, 90, 20, '', $aFontPadrao, 'T', 'L', 1);
        if (!empty($this->codBarras)) {
            $this->pTextBox($x + 72, $y + 1, 86, 4, "CÓDIGO DE BARRAS FATURA", $aFontB, 'T', 'L', 0);
            
            // Renderiza barcode 128
            try {
                $this->pdf->code128($x + 72, $y + 6, $this->codBarras, 86, 8);
            } catch (Exception $e) {
                // Silenciado
            }
            $this->pTextBox($x + 72, $y + 15, 86, 4, $this->codBarras, array('font' => $this->fontePadrao, 'size' => 6, 'style' => ''), 'T', 'C', 0);
        } else {
            $this->pTextBox($x + 72, $y + 8, 86, 4, "-- SEM COBRANÇA REGISTRADA --", $aFontB, 'T', 'C', 0);
        }

        // PIX QRCode Box
        $this->pTextBox($x + 160, $y, 38, 20, '', $aFontPadrao, 'T', 'L', 1);
        if (!empty($this->urlPix)) {
            $nameTMP = 'pix_' . $this->chave . '.png';
            $folder = __DIR__ . '/../Legacy/QRCode/tmp/';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            $qrPath = $folder . $nameTMP;
            
            require_once __DIR__ . '/../Legacy/QRCode/qrlib.php';
            \QRCode::png(htmlspecialchars_decode($this->urlPix), $qrPath, 'M', 4, 1);
            
            if (file_exists($qrPath)) {
                $this->pdf->Image($qrPath, $x + 171, $y + 2, 16, 16, 'png');
                @unlink($qrPath);
            }
        } else {
            $this->pTextBox($x + 160, $y, 38, 20, "PIX\nNÃO DISPONÍVEL", $aFontB, 'C', 'C', 0);
        }
    }

    public function render()
    {
        return $this->pdf->getPdf();
    }
}
