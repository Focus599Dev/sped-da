<?php

namespace NFePHP\DA\NFe;

use InvalidArgumentException;
use NFePHP\DA\Legacy\Pdf;

/**
 * Classe DanfeV2
 *
 * Implementa o Documento Auxiliar da Nota Fiscal Eletrônica (DANFE)
 * conforme as especificações da Nota Técnica 2026.010 (DANFE Reforma Tributária - IBS/CBS/IS).
 *
 * 100% Compatível com PHP 7.2.
 */
class DanfeV2 extends Danfe
{
    /**
     * Nó de totais do IBS e CBS (IBSCBSTot)
     * @var object
     */
    protected $IBSCBSTot;

    /**
     * Nó de totais do Imposto Seletivo (ISTot)
     * @var object
     */
    protected $ISTot;

    /**
     * Nó de informações suplementares (infNFeSupl)
     * @var object
     */
    protected $infNFeSupl;

    /**
     * Conteúdo do QR Code
     * @var string
     */
    protected $qrCodeDanfe = '';

    /**
     * Construtor da classe DanfeV2
     *
     * @param string  $docXML      Conteúdo XML da NF-e
     * @param string  $sOrientacao 'P' para Retrato ou 'L' para Paisagem
     * @param string  $sPapel      Tamanho do papel ('A4')
     * @param string  $sPathLogo   Caminho para o logotipo
     * @param string  $sDestino    Destino do PDF ('I', 'D', 'F', 'S')
     * @param string  $sDirPDF     Diretório para salvar o PDF
     * @param string  $fonteDANFE  Nome da fonte alternativa
     * @param integer $mododebug   Modo debug (0, 1 ou 2)
     * @param array   $customDanfe Configurações customizadas
     */
    public function __construct(
        $docXML = '',
        $sOrientacao = '',
        $sPapel = '',
        $sPathLogo = '',
        $sDestino = 'I',
        $sDirPDF = '',
        $fonteDANFE = '',
        $mododebug = 2,
        $customDanfe = array()
    ) {
        parent::__construct(
            $docXML,
            $sOrientacao,
            $sPapel,
            $sPathLogo,
            $sDestino,
            $sDirPDF,
            $fonteDANFE,
            $mododebug,
            $customDanfe
        );

        if (!empty($this->dom)) {
            $this->IBSCBSTot = $this->dom->getElementsByTagName("IBSCBSTot")->item(0);
            $this->ISTot = $this->dom->getElementsByTagName("ISTot")->item(0);
            $this->infNFeSupl = $this->dom->getElementsByTagName("infNFeSupl")->item(0);

            if ($this->infNFeSupl) {
                $qrNode = $this->infNFeSupl->getElementsByTagName("qrCode")->item(0);
                if ($qrNode) {
                    $this->qrCodeDanfe = trim($qrNode->nodeValue);
                }
            }
        }
    }

    /**
     * Retorna a descrição legível do CRT
     *
     * @param string $crt
     * @return string
     */
    protected function pGetDescricaoCRT($crt)
    {
        switch (trim((string)$crt)) {
            case '1':
                return '1 - SIMPLES NACIONAL';
            case '2':
                return '2 - SIMPLES NACIONAL - EXCESSO DE SUBLIMITE';
            case '3':
                return '3 - REGIME NORMAL';
            case '4':
                return '4 - SIMPLES NACIONAL - MEI';
            default:
                return $crt != '' ? $crt : '3 - REGIME NORMAL';
        }
    }

    /**
     * Cabeçalho do DANFE adequado à NT 2026.010 (incluindo CRT e Regime de Apuração)
     *
     * @param number $x
     * @param number $y
     * @param string $pag
     * @param string $totPag
     * @return number Posição vertical final
     */
    protected function pCabecalhoDANFE($x = 0, $y = 0, $pag = '1', $totPag = '1')
    {
        $oldX = $x;
        $oldY = $y;

        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            if ($pag == 1) {
                $maxW = $this->wPrint - $this->wCanhoto;
            } else {
                $maxW = $this->wPrint;
            }
        }

        // Coluna 1: Identificação do Emitente
        $w = round($maxW * 0.41, 0);
        $w1 = $w;
        $h = 32;
        $oldY += $h;

        $aFont = ($this->orientacao == 'P')
            ? array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'I')
            : array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');

        $this->pTextBox($x, $y, $w, $h);
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->pTextBox($x, $y, $w, 5, $texto, $aFont, 'T', 'C', 0, '');

        if (is_file($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            $logoWmm = ($logoInfo[0] / 72) * 25.4;
            $logoHmm = ($logoInfo[1] / 72) * 25.4;

            if ($this->logoAlign == 'L') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = $x + 1;
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                $x1 = round($xImg + $nImgW + 1, 0);
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            } elseif ($this->logoAlign == 'R') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = round($x + ($w - (1 + $nImgW)), 0);
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                $x1 = $x;
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            } elseif ($this->logoAlign == 'F') {
                $nImgH = round($h - 5, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg = round(($w - $nImgW) / 2 + $x, 0);
                $yImg = $y + 3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            } else { // Center (C)
                $nImgH = round($h / 3, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg = round(($w - $nImgW) / 2 + $x, 0);
                $yImg = $y + 3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH);
        } else {
            $x1 = $x;
            $y1 = round($h / 3 + $y, 0);
            $tw = $w;
        }

        if ($this->logoAlign !== 'F') {
            $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'B');
            $texto = $this->emit->getElementsByTagName("xNome")->item(0)->nodeValue;
            $this->pTextBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');

            $y1 = $y1 + 5;
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $fone = !empty($this->enderEmit->getElementsByTagName("fone")->item(0)->nodeValue)
                ? $this->enderEmit->getElementsByTagName("fone")->item(0)->nodeValue
                : '';
            $lgr = $this->pSimpleGetValue($this->enderEmit, "xLgr");
            $nro = $this->pSimpleGetValue($this->enderEmit, "nro");
            $cpl = $this->pSimpleGetValue($this->enderEmit, "xCpl", " - ");
            $bairro = $this->pSimpleGetValue($this->enderEmit, "xBairro");
            $CEP = $this->pSimpleGetValue($this->enderEmit, "CEP");
            $CEP = $this->pFormat($CEP, "#####-###");
            if (strlen($fone) > 10) {
                $fone = $this->pFormat($fone, "(##) #####-####");
            } else {
                $fone = $this->pFormat($fone, "(##) ####-####");
            }
            $mun = $this->pSimpleGetValue($this->enderEmit, "xMun");
            $UF = $this->pSimpleGetValue($this->enderEmit, "UF");
            $texto = $lgr . ", " . $nro . $cpl . " - " . $bairro . "\n"
                . $CEP . " - " . $mun . " | " . $UF . "\n"
                . "Fone: " . $fone;
            $this->pTextBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        }

        // Coluna 2: DANFE Central
        $x += $w;
        $w = round($maxW * 0.17, 0);
        $w2 = $w;
        $h = 32;
        $this->pTextBox($x, $y, $w, $h);

        if (!$this->pNotaCancelada()) {
            $texto = "DANFE";
            $aFont = array('font' => $this->fontePadrao, 'size' => 14, 'style' => 'B');
            $this->pTextBox($x, $y + 1, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $texto = 'Documento Auxiliar da Nota Fiscal Eletrônica';
            $this->pTextBox($x, $y + 6, $w, 20, $texto, $aFont, 'T', 'C', 0, '', false);
        }

        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pTextBox($x + 2, $y + 14, $w, 8, '0 - ENTRADA', $aFont, 'T', 'L', 0, '');
        $this->pTextBox($x + 2, $y + 17, $w, 8, '1 - SAÍDA', $aFont, 'T', 'L', 0, '');

        $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'B');
        $tpNF = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;
        $this->pTextBox($x + 27, $y + 13, 5, 7, $tpNF, $aFont, 'C', 'C', 1, '');

        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $numNF = str_pad($this->ide->getElementsByTagName('nNF')->item(0)->nodeValue, 9, "0", STR_PAD_LEFT);
        $numNF = $this->pFormat($numNF, "#########");
        $this->pTextBox($x, $y + 20, $w, 7, "Nº. " . $numNF, $aFont, 'C', 'C', 0, '');

        $serie = str_pad($this->ide->getElementsByTagName('serie')->item(0)->nodeValue, 3, "0", STR_PAD_LEFT);
        $this->pTextBox($x, $y + 23, $w, 7, "Série " . $serie, $aFont, 'C', 'C', 0, '');

        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'I');
        $this->pTextBox($x, $y + 26, $w, 7, "Folha " . $pag . "/" . $totPag, $aFont, 'C', 'C', 0, '');

        // Coluna 3: Código de Barras e Chave
        $x += $w;
        $w = ($maxW - $w1 - $w2);
        $w3 = $w;
        $h = 32;
        $this->pTextBox($x, $y, $w, $h);
        $this->pdf->SetFillColor(0, 0, 0);
        $chave_acesso = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $bW = 75;
        $bH = 12;

        $this->pdf->Code128($x + (($w - $bW) / 2), $y + 2, $chave_acesso, $bW, $bH);
        $this->pdf->Line($x, $y + 4 + $bH, $x + $w, $y + 4 + $bH);
        $this->pdf->Line($x, $y + 12 + $bH, $x + $w, $y + 12 + $bH);

        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y + 4 + $bH, $w, 7, 'CHAVE DE ACESSO', $aFont, 'T', 'L', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $this->pTextBox($x + 2, $y + 8 + $bH, $w - 2, 7, $this->pFormat($chave_acesso, $this->formatoChave), $aFont, 'T', 'C', 0, '');

        $y1 = $y + 12 + $bH;
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $cabecalhoProtoAutorizacao = 'PROTOCOLO DE AUTORIZAÇÃO DE USO';
        if ($this->pNotaDPEC()) {
            $cabecalhoProtoAutorizacao = 'NÚMERO DE REGISTRO EPEC';
        } elseif (($this->tpEmis == 2 || $this->tpEmis == 5) && !$this->pNotaDPEC()) {
            $cabecalhoProtoAutorizacao = "DADOS DA NF-E";
        }

        if (($this->tpEmis == 2 || $this->tpEmis == 5) && !$this->pNotaDPEC()) {
            $chaveContingencia = $this->pGeraChaveAdicionalDeContingencia();
            $this->pdf->SetFillColor(0, 0, 0);
            $this->pdf->Code128($x + 11, $y1 + 1, $chaveContingencia, $bW * .9, $bH / 2);
        } else {
            $this->pTextBox($x + 2, $y1, $w - 2, 7, 'Consulta de autenticidade no portal nacional da NF-e', $aFont, 'T', 'C', 0, '');
            $this->pTextBox(
                $x + 2,
                $y + 16 + $bH,
                $w - 2,
                7,
                'www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora',
                $aFont,
                'T',
                'C',
                0,
                'http://www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora'
            );
        }

        // Linha: Natureza da Operação e Protocolo
        $y = $oldY;
        $oldY += 7;
        $x = $oldX;
        $h = 7;
        $w = $w1 + $w2;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y, $w, $h, 'NATUREZA DA OPERAÇÃO', $aFont, 'T', 'L', 1, '');
        $textoNatOp = $this->ide->getElementsByTagName("natOp")->item(0)->nodeValue;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pTextBox($x, $y, $w, $h, $textoNatOp, $aFont, 'B', 'C', 0, '');

        $x += $w;
        $w = $w3;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y, $w, $h, $cabecalhoProtoAutorizacao, $aFont, 'T', 'L', 1, '');

        $textoProto = '';
        if (($this->tpEmis == 2 || $this->tpEmis == 5) && !$this->pNotaDPEC()) {
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $textoProto = $this->pFormat($chaveContingencia, "#### #### #### #### #### #### #### #### ####");
        } else {
            $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
            if ($this->pNotaDPEC()) {
                $textoProto = $this->numero_registro_dpec;
            } elseif (isset($this->nfeProc)) {
                $nProt = !empty($this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue)
                    ? $this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue : '';
                $dhRecbto = !empty($this->nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue)
                    ? $this->nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue : '';
                if ($nProt != '') {
                    $tsHora = $this->pConvertTime($dhRecbto);
                    $textoProto = $nProt . "  -  " . date('d/m/Y H:i:s', $tsHora);
                }
            }
        }
        $this->pTextBox($x, $y, $w, $h, $textoProto, $aFont, 'B', 'C', 0, '');

        // Linha: Inscrição Estadual, IEST e CNPJ
        $w = round($maxW * 0.333, 0);
        $y += $h;
        $oldY += $h;
        $x = $oldX;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y, $w, $h, 'INSCRIÇÃO ESTADUAL', $aFont, 'T', 'L', 1, '');
        $textoIE = $this->pSimpleGetValue($this->emit, "IE");
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pTextBox($x, $y, $w, $h, $textoIE, $aFont, 'B', 'C', 0, '');

        $x += $w;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y, $w, $h, 'INSCRIÇÃO ESTADUAL DO SUBST. TRIBUTÁRIO', $aFont, 'T', 'L', 1, '');
        $textoIEST = !empty($this->emit->getElementsByTagName("IEST")->item(0)->nodeValue)
            ? $this->emit->getElementsByTagName("IEST")->item(0)->nodeValue : '';
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pTextBox($x, $y, $w, $h, $textoIEST, $aFont, 'B', 'C', 0, '');

        $x += $w;
        $wCnpj = ($maxW - (2 * $w));
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y, $wCnpj, $h, 'CNPJ / CPF', $aFont, 'T', 'L', 1, '');
        $textoCnpj = '';
        if (!empty($this->emit->getElementsByTagName("CNPJ")->item(0)->nodeValue)) {
            $textoCnpj = $this->pFormat($this->emit->getElementsByTagName("CNPJ")->item(0)->nodeValue, "###.###.###/####-##");
        } elseif (!empty($this->emit->getElementsByTagName("CPF")->item(0)->nodeValue)) {
            $textoCnpj = $this->pFormat($this->emit->getElementsByTagName("CPF")->item(0)->nodeValue, "###.###.###-##");
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pTextBox($x, $y, $wCnpj, $h, $textoCnpj, $aFont, 'B', 'C', 0, '');

        // ####################################################################################
        // NOVA LINHA NT 2026.010: CÓDIGO DO REGIME TRIBUTÁRIO & REGIME DE APURAÇÃO IBS/CBS
        // ####################################################################################
        $y += $h;
        $oldY += $h;
        $x = $oldX;
        $wMetade = round($maxW * 0.5, 0);

        // Coluna Esquerda: Código do Regime Tributário (CRT)
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y, $wMetade, $h, 'CÓDIGO DO REGIME TRIBUTÁRIO', $aFont, 'T', 'L', 1, '');
        $crtValor = $this->pSimpleGetValue($this->emit, "CRT");
        $textoCRT = $this->pGetDescricaoCRT($crtValor);
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->pTextBox($x, $y, $wMetade, $h, $textoCRT, $aFont, 'B', 'C', 0, '');

        // Coluna Direita: Tipo de Regime de Apuração do IBS e da CBS (reservado NT futura)
        $x += $wMetade;
        $wResto = $maxW - $wMetade;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $this->pTextBox($x, $y, $wResto, $h, 'TIPO DE REGIME DE APURAÇÃO DO IBS E DA CBS', $aFont, 'T', 'L', 1, '');
        // Campo reservado: não imprimir conteúdo enquanto não houver definição de tag conforme NT 2026.010

        return $oldY;
    }

    /**
     * Bloco de Impostos do DANFE adaptado para a NT 2026.010
     *
     * Inclui:
     * 1. Total dos Produtos e Total da Nota
     * 2. Total do ICMS / IPI
     * 3. Total do IBS / CBS / IS
     *
     * @param float $x
     * @param float $y
     * @return float Posição vertical final
     */
    protected function pImpostoDANFE($x, $y)
    {
        $x_inicial = $x;

        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }

        $h = 7;
        $aFontTitulo = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');

        // #####################################################################
        // BLOCO 1: TOTAL DOS PRODUTOS E TOTAL DA NOTA
        // #####################################################################
        $this->pTextBox($x, $y, $maxW, 8, 'TOTAL DOS PRODUTOS E TOTAL DA NOTA', $aFontTitulo, 'T', 'L', 0, '');
        $y += 3;
        $w6 = $maxW / 6;

        $x = $this->pImpostoDanfeHelper($x, $y, $w6, $h, "VALOR TOTAL DOS PRODUTOS", "vProd");
        $x = $this->pImpostoDanfeHelper($x, $y, $w6, $h, "VALOR DO FRETE", "vFrete");
        $x = $this->pImpostoDanfeHelper($x, $y, $w6, $h, "VALOR DO SEGURO", "vSeg");
        $x = $this->pImpostoDanfeHelper($x, $y, $w6, $h, "DESCONTO", "vDesc");

        if ($this->ICMSTot && $this->ICMSTot->getElementsByTagName('vIPIDevol')->length > 0) {
            $vIPIDevol = (float)$this->ICMSTot->getElementsByTagName('vIPIDevol')->item(0)->nodeValue;
            if ($vIPIDevol != 0.00) {
                $vOutroNode = $this->ICMSTot->getElementsByTagName('vOutro')->item(0);
                if ($vOutroNode) {
                    $vOutroNode->nodeValue = (float)$vOutroNode->nodeValue + $vIPIDevol;
                }
            }
        }

        $x = $this->pImpostoDanfeHelper($x, $y, $w6, $h, "OUTRAS DESPESAS", "vOutro");
        $x = $this->pImpostoDanfeHelper($x, $y, $w6, $h, "VALOR TOTAL DA NOTA", "vNF");

        // #####################################################################
        // BLOCO 2: TOTAL DO ICMS / IPI
        // #####################################################################
        $y += $h + 1;
        $x = $x_inicial;
        $this->pTextBox($x, $y, $maxW, 8, 'TOTAL DO ICMS / IPI', $aFontTitulo, 'T', 'L', 0, '');
        $y += 3;
        $w5 = $maxW / 5;

        // Linha 1: ICMS normal, ICMS ST e IPI
        $x = $this->pImpostoDanfeHelper($x, $y, $w5, $h, "BASE DE CÁLCULO DO ICMS", "vBC");
        $x = $this->pImpostoDanfeHelper($x, $y, $w5, $h, "VALOR DO ICMS", "vICMS");
        $x = $this->pImpostoDanfeHelper($x, $y, $w5, $h, "BASE DE CÁLCULO DO ICMS ST", "vBCST");
        $x = $this->pImpostoDanfeHelper($x, $y, $w5, $h, "VALOR DO ICMS ST", "vST");
        $x = $this->pImpostoDanfeHelper($x, $y, $w5, $h, "VALOR DO IPI", "vIPI");

        // Linha 2 (Facultativa/Condicional): FCP e DIFAL
        $temFCPouDifal = false;
        if ($this->ICMSTot) {
            $vFCP = (float)$this->pSimpleGetValue($this->ICMSTot, 'vFCP');
            $vFCPST = (float)$this->pSimpleGetValue($this->ICMSTot, 'vFCPST');
            $vICMSUFDest = (float)$this->pSimpleGetValue($this->ICMSTot, 'vICMSUFDest');
            $vFCPUFDest = (float)$this->pSimpleGetValue($this->ICMSTot, 'vFCPUFDest');
            if ($vFCP > 0 || $vFCPST > 0 || $vICMSUFDest > 0 || $vFCPUFDest > 0) {
                $temFCPouDifal = true;
            }
        }

        if ($temFCPouDifal) {
            $y += $h;
            $x = $x_inicial;
            $w4 = $maxW / 4;
            $x = $this->pImpostoDanfeHelper($x, $y, $w4, $h, "VALOR DO FCP", "vFCP");
            $x = $this->pImpostoDanfeHelper($x, $y, $w4, $h, "VALOR DO FCP RETIDO POR ST", "vFCPST");
            $x = $this->pImpostoDanfeHelper($x, $y, $w4, $h, "VALOR DO DIFAL NA UF DE DESTINO", "vICMSUFDest");
            $x = $this->pImpostoDanfeHelper($x, $y, $w4, $h, "VALOR DO FCP NA UF DE DESTINO", "vFCPUFDest");
        }

        // #####################################################################
        // BLOCO 3: TOTAL DO IBS / CBS / IS (NT 2026.010)
        // #####################################################################
        $y += $h + 1;
        $x = $x_inicial;
        $this->pTextBox($x, $y, $maxW, 8, 'TOTAL DO IBS / CBS / IS', $aFontTitulo, 'T', 'L', 0, '');
        $y += 3;
        $w4 = $maxW / 4;

        // Obter valores da Reforma Tributária do nó IBSCBSTot e ISTot
        $vCBS = '0,00';
        $vIBSUF = '0,00';
        $vIBSMun = '0,00';
        $vIS = '0,00';
        $vIBSMono = '0,00';
        $vCBSMono = '0,00';
        $vIBSMonoReten = '0,00';
        $vCBSMonoReten = '0,00';
        $temMono = false;

        if ($this->IBSCBSTot) {
            $gCBS = $this->IBSCBSTot->getElementsByTagName("gCBS")->item(0);
            if ($gCBS) {
                $vCBS = $this->pFormatCurrency($this->pSimpleGetValue($gCBS, "vCBS"));
            } else {
                $vCBSVal = $this->pSimpleGetValue($this->IBSCBSTot, "vCBS");
                if ($vCBSVal != '') {
                    $vCBS = $this->pFormatCurrency($vCBSVal);
                }
            }

            $gIBS = $this->IBSCBSTot->getElementsByTagName("gIBS")->item(0);
            if ($gIBS) {
                $gIBSUF = $gIBS->getElementsByTagName("gIBSUF")->item(0);
                if ($gIBSUF) {
                    $vIBSUF = $this->pFormatCurrency($this->pSimpleGetValue($gIBSUF, "vIBSUF"));
                }
                $gIBSMun = $gIBS->getElementsByTagName("gIBSMun")->item(0);
                if ($gIBSMun) {
                    $vIBSMun = $this->pFormatCurrency($this->pSimpleGetValue($gIBSMun, "vIBSMun"));
                }
            }

            $gMono = $this->IBSCBSTot->getElementsByTagName("gMono")->item(0);
            if ($gMono) {
                $vIBSMonoVal = $this->pSimpleGetValue($gMono, "vIBSMono");
                $vCBSMonoVal = $this->pSimpleGetValue($gMono, "vCBSMono");
                $vIBSMonoRetenVal = $this->pSimpleGetValue($gMono, "vIBSMonoReten");
                $vCBSMonoRetenVal = $this->pSimpleGetValue($gMono, "vCBSMonoReten");

                if ($vIBSMonoVal != '' || $vCBSMonoVal != '' || $vIBSMonoRetenVal != '' || $vCBSMonoRetenVal != '') {
                    $temMono = true;
                    $vIBSMono = $this->pFormatCurrency($vIBSMonoVal);
                    $vCBSMono = $this->pFormatCurrency($vCBSMonoVal);
                    $vIBSMonoReten = $this->pFormatCurrency($vIBSMonoRetenVal);
                    $vCBSMonoReten = $this->pFormatCurrency($vCBSMonoRetenVal);
                }
            }
        }

        if ($this->ISTot) {
            $vISVal = $this->pSimpleGetValue($this->ISTot, "vIS");
            if ($vISVal != '') {
                $vIS = $this->pFormatCurrency($vISVal);
            }
        }

        // Linha 1 de IBS/CBS/IS (4 colunas)
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DA CBS", $vCBS);
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DO IBS UF", $vIBSUF);
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DO IBS MUNICÍPIO", $vIBSMun);
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DO IMPOSTO SELETIVO", $vIS);

        // Linha 2 de IBS/CBS/IS (Monofásicos - condicional)
        if ($temMono) {
            $y += $h;
            $x = $x_inicial;
            $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DO IBS MONOFÁSICO", $vIBSMono);
            $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DA CBS MONOFÁSICA", $vCBSMono);
            $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DO IBS MONOFÁSICO POR RETENÇÃO", $vIBSMonoReten);
            $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DA CBS MONOFÁSICA POR RETENÇÃO", $vCBSMonoReten);
        }

        return ($y + $h);
    }

    /**
     * Helper para desenhar célula com rótulo e valor personalizado
     *
     * @param float  $x
     * @param float  $y
     * @param float  $w
     * @param float  $h
     * @param string $titulo
     * @param string $valor
     * @return float Próximo X
     */
    protected function pCustomFieldBox($x, $y, $w, $h, $titulo, $valor)
    {
        $aFontLabel = array('font' => $this->fontePadrao, 'size' => 6, 'style' => '');
        $aFontValue = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');

        $this->pTextBox($x, $y, $w, $h, $titulo, $aFontLabel, 'T', 'L', 1, '');
        $this->pTextBox($x, $y, $w, $h, $valor, $aFontValue, 'B', 'R', 0, '');

        return ($x + $w);
    }

    /**
     * Bloco de Cálculo do ISSQN conforme NT 2026.010
     *
     * @param float $x
     * @param float $y
     * @return float Posição vertical final
     */
    protected function pIssqnDANFE($x, $y)
    {
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }

        $h = 7;
        $aFontTitulo = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        $this->pTextBox($x, $y, $maxW, 8, 'CÁLCULO DO ISSQN', $aFontTitulo, 'T', 'L', 0, '');

        $y += 3;
        $w4 = $maxW / 4;

        // 1. Inscrição Municipal
        $im = !empty($this->emit->getElementsByTagName("IM")->item(0)->nodeValue)
            ? $this->emit->getElementsByTagName("IM")->item(0)->nodeValue
            : '—';
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "INSCRIÇÃO MUNICIPAL", $im);

        // 2. Valor Total dos Serviços
        $vServ = '0,00';
        if (isset($this->ISSQNtot)) {
            $vServVal = $this->pSimpleGetValue($this->ISSQNtot, 'vServ');
            if ($vServVal != '') {
                $vServ = $this->pFormatCurrency($vServVal);
            }
        }
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR TOTAL DOS SERVIÇOS", $vServ);

        // 3. Base de Cálculo do ISSQN
        $vBcISS = '0,00';
        if (isset($this->ISSQNtot)) {
            $vBcISSVal = $this->pSimpleGetValue($this->ISSQNtot, 'vBC');
            if ($vBcISSVal != '') {
                $vBcISS = $this->pFormatCurrency($vBcISSVal);
            }
        }
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "BASE DE CÁLCULO DO ISSQN", $vBcISS);

        // 4. Valor do ISSQN
        $vISS = '0,00';
        if (isset($this->ISSQNtot)) {
            $vISSVal = $this->pSimpleGetValue($this->ISSQNtot, 'vISS');
            if ($vISSVal != '') {
                $vISS = $this->pFormatCurrency($vISSVal);
            }
        }
        $x = $this->pCustomFieldBox($x, $y, $w4, $h, "VALOR DO ISSQN", $vISS);

        return ($y + $h);
    }

    /**
     * Formata moeda/valor decimal
     *
     * @param mixed $valor
     * @param int   $decimais
     * @return string
     */
    protected function pFormatCurrency($valor, $decimais = 2)
    {
        if ($valor === '' || $valor === null) {
            return '0,00';
        }
        return number_format((float)$valor, $decimais, ',', '.');
    }

    /**
     * Formata percentual
     *
     * @param mixed $valor
     * @param int   $decimais
     * @return string
     */
    protected function pFormatPercent($valor, $decimais = 2)
    {
        if ($valor === '' || $valor === null) {
            return '0,00%';
        }
        return number_format((float)$valor, $decimais, ',', '.') . '%';
    }

    /**
     * Tabela de Produtos/Serviços adaptada para a Reforma Tributária (NT 2026.010)
     *
     * @param float   $x
     * @param float   $y
     * @param integer &$nInicio
     * @param float   $hmax
     * @param integer $pag
     * @param integer &$totpag
     * @param float   $hCabecItens
     * @param boolean $hasTagMed
     * @param boolean $hasTagICMSST
     * @return float Posição vertical final
     */
    protected function pItensDANFE(
        $x,
        $y,
        &$nInicio,
        $hmax,
        $pag = 0,
        &$totpag = 0,
        $hCabecItens = 7,
        $hasTagMed = false,
        $hasTagICMSST = false
    ) {
        $oldX = $x;
        $oldY = $y;
        $totItens = $this->det->length;

        $texto = "DADOS DOS PRODUTOS / SERVIÇOS";
        if ($this->orientacao == 'P') {
            $w = $this->wPrint;
        } else {
            $w = ($nInicio < 2) ? ($this->wPrint - $this->wCanhoto) : $this->wPrint;
        }

        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        $this->pTextBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;

        // Caixa externa
        $hmax += 1;
        $this->pTextBox($x, $y, $w, $hmax);

        // #####################################################################
        // LARGURAS DAS COLUNAS (NT 2026.010)
        // #####################################################################
        // 1. Descrição do Produto / Serviço (~29%)
        // 2. CST / CFOP (~8%)
        // 3. QTD / UN (~10%)
        // 4. VLR UNIT (~9%)
        // 5. VLR TOTAL (~9%)
        // 6. BASES DE CÁLCULO (~14%)
        // 7. ALÍQUOTAS (~10%)
        // 8. VALOR DOS TRIBUTOS (~11%)
        $wDescricao = round($w * 0.29, 0);
        $wCstCfop   = round($w * 0.08, 0);
        $wQtdUn     = round($w * 0.10, 0);
        $wVlrUnit   = round($w * 0.09, 0);
        $wVlrTotal  = round($w * 0.09, 0);
        $wBasesCalc = round($w * 0.14, 0);
        $wAliquotas = round($w * 0.10, 0);
        $wVlrTrib   = $w - ($wDescricao + $wCstCfop + $wQtdUn + $wVlrUnit + $wVlrTotal + $wBasesCalc + $wAliquotas);

        $hCab = 5;
        $aFontCab = array('font' => $this->fontePadrao, 'size' => 5.5, 'style' => 'B');

        // Cabeçalhos
        $colX = $x;
        $this->pTextBox($colX, $y, $wDescricao, $hCab, "DESCRIÇÃO DO PRODUTO / SERVIÇO", $aFontCab, 'C', 'C', 0, '', false);
        $this->pdf->Line($colX + $wDescricao, $y, $colX + $wDescricao, $y + $hmax);

        $colX += $wDescricao;
        $this->pTextBox($colX, $y, $wCstCfop, $hCab, "CST / CFOP", $aFontCab, 'C', 'C', 0, '', false);
        $this->pdf->Line($colX + $wCstCfop, $y, $colX + $wCstCfop, $y + $hmax);

        $colX += $wCstCfop;
        $this->pTextBox($colX, $y, $wQtdUn, $hCab, "QTD / UN", $aFontCab, 'C', 'C', 0, '', false);
        $this->pdf->Line($colX + $wQtdUn, $y, $colX + $wQtdUn, $y + $hmax);

        $colX += $wQtdUn;
        $this->pTextBox($colX, $y, $wVlrUnit, $hCab, "VLR UNIT", $aFontCab, 'C', 'C', 0, '', false);
        $this->pdf->Line($colX + $wVlrUnit, $y, $colX + $wVlrUnit, $y + $hmax);

        $colX += $wVlrUnit;
        $this->pTextBox($colX, $y, $wVlrTotal, $hCab, "VLR TOTAL", $aFontCab, 'C', 'C', 0, '', false);
        $this->pdf->Line($colX + $wVlrTotal, $y, $colX + $wVlrTotal, $y + $hmax);

        $colX += $wVlrTotal;
        $this->pTextBox($colX, $y, $wBasesCalc, $hCab, "BASES DE CÁLCULO", $aFontCab, 'C', 'C', 0, '', false);
        $this->pdf->Line($colX + $wBasesCalc, $y, $colX + $wBasesCalc, $y + $hmax);

        $colX += $wBasesCalc;
        $this->pTextBox($colX, $y, $wAliquotas, $hCab, "ALÍQUOTAS", $aFontCab, 'C', 'C', 0, '', false);
        $this->pdf->Line($colX + $wAliquotas, $y, $colX + $wAliquotas, $y + $hmax);

        $colX += $wAliquotas;
        $this->pTextBox($colX, $y, $wVlrTrib, $hCab, "VALOR DOS TRIBUTOS", $aFontCab, 'C', 'C', 0, '', false);

        $this->pdf->Line($oldX, $y + $hCab, $oldX + $w, $y + $hCab);
        $y += $hCab + 1;

        // #####################################################################
        // LOOP COM OS ITENS
        // #####################################################################
        $i = 0;
        $hUsado = $hCabecItens;
        $aFontProd = array('font' => $this->fontePadrao, 'size' => 6.5, 'style' => '');
        $aFontSub  = array('font' => $this->fontePadrao, 'size' => 5.2, 'style' => '');
        $aFontTrib = array('font' => $this->fontePadrao, 'size' => 4.8, 'style' => '');

        foreach ($this->det as $d) {
            if ($i >= $nInicio) {
                $thisItem = $this->det->item($i);
                $prod = $thisItem->getElementsByTagName("prod")->item(0);
                $imposto = $thisItem->getElementsByTagName("imposto")->item(0);

                // Dados básicos do produto
                $xProd = $this->pSimpleGetValue($prod, "xProd");
                $infAdProd = $this->pSimpleGetValue($thisItem, "infAdProd");
                $ncm = $this->pSimpleGetValue($prod, "NCM");
                $cfop = $this->pSimpleGetValue($prod, "CFOP");
                $uCom = $this->pSimpleGetValue($prod, "uCom");
                $qCom = (float)$this->pSimpleGetValue($prod, "qCom");
                $vUnCom = (float)$this->pSimpleGetValue($prod, "vUnCom");
                $vProd = (float)$this->pSimpleGetValue($prod, "vProd");

                // Identificar cClassTrib do IBS/CBS
                $cClassTrib = '';
                $cstIBS = '';
                $ibscbsNode = $imposto ? $imposto->getElementsByTagName("IBSCBS")->item(0) : null;
                if ($ibscbsNode) {
                    $cClassTrib = $this->pSimpleGetValue($ibscbsNode, "cClassTrib");
                    $cstIBS = $this->pSimpleGetValue($ibscbsNode, "CST");
                }

                // ICMS
                $icmsNode = $imposto ? $imposto->getElementsByTagName("ICMS")->item(0) : null;
                $origem = $icmsNode ? $this->pSimpleGetValue($icmsNode, "orig") : '';
                $cstICMS = $icmsNode ? $this->pSimpleGetValue($icmsNode, "CST") : '';
                $csosnICMS = $icmsNode ? $this->pSimpleGetValue($icmsNode, "CSOSN") : '';
                $cstExib = $origem . ($cstICMS != '' ? $cstICMS : ($csosnICMS != '' ? $csosnICMS : $cstIBS));

                $vBcICMS = $icmsNode ? (float)$this->pSimpleGetValue($icmsNode, "vBC") : 0.0;
                $pICMS = $icmsNode ? (float)$this->pSimpleGetValue($icmsNode, "pICMS") : 0.0;
                $vICMS = $icmsNode ? (float)$this->pSimpleGetValue($icmsNode, "vICMS") : 0.0;

                // IPI
                $ipiNode = $imposto ? $imposto->getElementsByTagName("IPI")->item(0) : null;
                $vBcIPI = $ipiNode ? (float)$this->pSimpleGetValue($ipiNode, "vBC") : 0.0;
                $pIPI = $ipiNode ? (float)$this->pSimpleGetValue($ipiNode, "pIPI") : 0.0;
                $vIPI = $ipiNode ? (float)$this->pSimpleGetValue($ipiNode, "vIPI") : 0.0;

                // IBS/CBS
                $vBcIBSCBS = 0.0;
                $pIBSUF = 0.0;
                $vIBSUF = 0.0;
                $pIBSMun = 0.0;
                $vIBSMun = 0.0;
                $pCBS = 0.0;
                $vCBS = 0.0;

                if ($ibscbsNode) {
                    $gIBSCBS = $ibscbsNode->getElementsByTagName("gIBSCBS")->item(0);
                    if ($gIBSCBS) {
                        $vBcIBSCBS = (float)$this->pSimpleGetValue($gIBSCBS, "vBC");

                        // IBS UF
                        $gIBSUF = $gIBSCBS->getElementsByTagName("gIBSUF")->item(0);
                        if ($gIBSUF) {
                            $vIBSUF = (float)$this->pSimpleGetValue($gIBSUF, "vIBSUF");
                            $gRedUF = $gIBSUF->getElementsByTagName("gRed")->item(0);
                            if ($gRedUF && $this->pSimpleGetValue($gRedUF, "pAliqEfet") != '') {
                                $pIBSUF = (float)$this->pSimpleGetValue($gRedUF, "pAliqEfet");
                            } else {
                                $pIBSUF = (float)$this->pSimpleGetValue($gIBSUF, "pIBSUF");
                            }
                        }

                        // IBS Município
                        $gIBSMun = $gIBSCBS->getElementsByTagName("gIBSMun")->item(0);
                        if ($gIBSMun) {
                            $vIBSMun = (float)$this->pSimpleGetValue($gIBSMun, "vIBSMun");
                            $gRedMun = $gIBSMun->getElementsByTagName("gRed")->item(0);
                            if ($gRedMun && $this->pSimpleGetValue($gRedMun, "pAliqEfet") != '') {
                                $pIBSMun = (float)$this->pSimpleGetValue($gRedMun, "pAliqEfet");
                            } else {
                                $pIBSMun = (float)$this->pSimpleGetValue($gIBSMun, "pIBSMun");
                            }
                        }

                        // CBS
                        $gCBS = $gIBSCBS->getElementsByTagName("gCBS")->item(0);
                        if ($gCBS) {
                            $vCBS = (float)$this->pSimpleGetValue($gCBS, "vCBS");
                            $gRedCBS = $gCBS->getElementsByTagName("gRed")->item(0);
                            if ($gRedCBS && $this->pSimpleGetValue($gRedCBS, "pAliqEfet") != '') {
                                $pCBS = (float)$this->pSimpleGetValue($gRedCBS, "pAliqEfet");
                            } else {
                                $pCBS = (float)$this->pSimpleGetValue($gCBS, "pCBS");
                            }
                        }
                    }
                }

                // Imposto Seletivo (IS)
                $vBcIS = 0.0;
                $pIS = 0.0;
                $vIS = 0.0;
                $isNode = $imposto ? $imposto->getElementsByTagName("IS")->item(0) : null;
                if ($isNode) {
                    $vBcISVal = $this->pSimpleGetValue($isNode, "vBCIS");
                    if ($vBcISVal == '') {
                        $vBcISVal = $this->pSimpleGetValue($isNode, "vBC");
                    }
                    $vBcIS = (float)$vBcISVal;
                    $pIS = (float)$this->pSimpleGetValue($isNode, "pIS");
                    $vIS = (float)$this->pSimpleGetValue($isNode, "vIS");
                }

                // Montagem da descrição conforme NT 2026.010:
                // Descrição do Produto
                // [informações adicionais]
                // [NCM ...] [cClassTrib ...]
                $textoLinha3 = "[NCM " . $ncm . "]";
                if ($cClassTrib != '') {
                    $textoLinha3 .= " [cClassTrib " . $cClassTrib . "]";
                }

                $textoDescTotal = $xProd;
                if ($infAdProd != '') {
                    $textoDescTotal .= "\n" . $infAdProd;
                }
                $textoDescTotal .= "\n" . $textoLinha3;

                // Cálculo da altura da linha: a célula de tributos tem 4 linhas (mínimo ~10mm)
                $linhasDesc = $this->pGetNumLines($textoDescTotal, $wDescricao, $aFontProd);
                $hItem = max(round($linhasDesc * 2.5, 2), 10.5);

                $hUsado += $hItem;

                if ($pag != $totpag) {
                    if ($hUsado >= ($hmax - $hItem) && $i < $totItens) {
                        $nInicio = $i;
                        break;
                    }
                } else {
                    if ($hUsado >= $hmax) {
                        $totpag = $totpag + 1;
                        $nInicio = $i;
                        break;
                    }
                }

                // Linha divisória tracejada
                $this->pdf->DashedHLine($oldX, $y + $hItem, $w, 0.1, 120);

                // Coluna 1: Descrição
                $colX = $oldX;
                $this->pTextBox($colX, $y, $wDescricao, $hItem, $textoDescTotal, $aFontProd, 'T', 'L', 0, '', false);

                // Coluna 2: CST / CFOP (2 linhas)
                $colX += $wDescricao;
                $textoCstCfop = "CST....." . str_pad($cstExib, 3, "0", STR_PAD_LEFT) . "\n"
                    . "CFOP.." . str_pad($cfop, 4, "0", STR_PAD_LEFT);
                $this->pTextBox($colX, $y, $wCstCfop, $hItem, $textoCstCfop, $aFontSub, 'T', 'C', 0, '', false);

                // Coluna 3: QTD / UN (2 linhas)
                $colX += $wCstCfop;
                $textoQtdUn = number_format($qCom, 4, ',', '.') . "\n[" . $uCom . "]";
                $this->pTextBox($colX, $y, $wQtdUn, $hItem, $textoQtdUn, $aFontSub, 'T', 'R', 0, '', false);

                // Coluna 4: VLR UNIT
                $colX += $wQtdUn;
                $this->pTextBox($colX, $y, $wVlrUnit, $hItem, number_format($vUnCom, 4, ',', '.'), $aFontSub, 'T', 'R', 0, '', false);

                // Coluna 5: VLR TOTAL
                $colX += $wVlrUnit;
                $this->pTextBox($colX, $y, $wVlrTotal, $hItem, number_format($vProd, 2, ',', '.'), $aFontSub, 'T', 'R', 0, '', false);

                // Coluna 6: BASES DE CÁLCULO (4 linhas: ICMS, IBS/CBS, IS, IPI)
                $colX += $wVlrTotal;
                $textoBases = "ICMS.........." . number_format($vBcICMS, 2, ',', '.') . "\n"
                    . "IBS / CBS..." . number_format($vBcIBSCBS, 2, ',', '.') . "\n"
                    . "IS................" . number_format($vBcIS, 2, ',', '.') . "\n"
                    . "IPI..............." . number_format($vBcIPI, 2, ',', '.');
                $this->pTextBox($colX, $y, $wBasesCalc, $hItem, $textoBases, $aFontTrib, 'T', 'L', 0, '', false);

                // Coluna 7: ALÍQUOTAS (3 linhas com 2 tributos: ICMS/CBS, IBS UF/IPI, IBS MUN/IS)
                $colX += $wBasesCalc;
                $textoAliq = "ICMS..." . $this->pFormatPercent($pICMS) . " / CBS..." . $this->pFormatPercent($pCBS) . "\n"
                    . "IBS UF." . $this->pFormatPercent($pIBSUF) . " / IPI..." . $this->pFormatPercent($pIPI) . "\n"
                    . "IBS MUN." . $this->pFormatPercent($pIBSMun) . " / IS...." . $this->pFormatPercent($pIS);
                $this->pTextBox($colX, $y, $wAliquotas, $hItem, $textoAliq, $aFontTrib, 'T', 'L', 0, '', false);

                // Coluna 8: VALOR DOS TRIBUTOS (3 linhas com 2 tributos)
                $colX += $wAliquotas;
                $textoVlrTrib = "ICMS..." . $this->pFormatCurrency($vICMS) . " / CBS..." . $this->pFormatCurrency($vCBS) . "\n"
                    . "IBS UF." . $this->pFormatCurrency($vIBSUF) . " / IPI..." . $this->pFormatCurrency($vIPI) . "\n"
                    . "IBS MUN." . $this->pFormatCurrency($vIBSMun) . " / IS...." . $this->pFormatCurrency($vIS);
                $this->pTextBox($colX, $y, $wVlrTrib, $hItem, $textoVlrTrib, $aFontTrib, 'T', 'L', 0, '', false);

                $y += $hItem;
                $i++;
                $this->qtdeItensProc++;
            } else {
                $i++;
            }
        }

        return $oldY + $hmax;
    }

    /**
     * Dados Adicionais e QR Code conforme a NT 2026.010
     *
     * @param float $x
     * @param float $y
     * @param float $h
     * @return float Posição vertical final
     */
    protected function pDadosAdicionaisDANFE($x, $y, $h)
    {
        $texto = "DADOS ADICIONAIS";
        if ($this->orientacao == 'P') {
            $wTotal = $this->wPrint;
        } else {
            $wTotal = $this->wPrint - $this->wCanhoto;
        }

        $aFontTitulo = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        $this->pTextBox($x, $y, $wTotal, 8, $texto, $aFontTitulo, 'T', 'L', 0, '');

        $y += 3;
        $aFontSec = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'B');

        // Se houver QR Code, reserva quadro à direita
        $wQRCode = 0;
        if (!empty($this->qrCodeDanfe)) {
            $wQRCode = 32; // 32mm de largura para o quadro QR Code
        }

        $wAdicionais = $wTotal - $wQRCode;

        // INFORMAÇÕES COMPLEMENTARES
        $wInfo = round($wAdicionais * 0.65, 0);
        $this->pTextBox($x, $y, $wInfo, $h, "INFORMAÇÕES COMPLEMENTARES", $aFontSec, 'T', 'L', 1, '');
        $aFontText = array('font' => $this->fontePadrao, 'size' => 6.5, 'style' => '');
        $this->pTextBox($x + 1, $y + 3, $wInfo - 2, $h - 4, $this->textoAdic, $aFontText, 'T', 'L', 0, '', false);

        // RESERVADO AO FISCO
        $xRes = $x + $wInfo;
        $wRes = $wAdicionais - $wInfo;
        $textoFisco = "RESERVADO AO FISCO\n";
        if ($this->nfeProc && $this->nfeProc->getElementsByTagName("xMsg")->item(0)) {
            $textoFisco .= ' ' . $this->nfeProc->getElementsByTagName("xMsg")->item(0)->nodeValue;
        }

        $xJust = $this->pSimpleGetValue($this->ide, 'xJust', 'Justificativa: ');
        $dhCont = $this->pSimpleGetValue($this->ide, 'dhCont', ' Entrada em contingência: ');
        switch ($this->tpEmis) {
            case 2:
                $textoFisco .= "\nCONTINGÊNCIA FS" . $dhCont . $xJust;
                break;
            case 3:
                $textoFisco .= "\nCONTINGÊNCIA SCAN" . $dhCont . $xJust;
                break;
            case 4:
                $textoFisco .= "\nCONTINGÊNCIA DPEC" . $dhCont . $xJust;
                break;
            case 5:
                $textoFisco .= "\nCONTINGÊNCIA FSDA" . $dhCont . $xJust;
                break;
            case 6:
                $textoFisco .= "\nCONTINGÊNCIA SVC-AN" . $dhCont . $xJust;
                break;
            case 7:
                $textoFisco .= "\nCONTINGÊNCIA SVC-RS" . $dhCont . $xJust;
                break;
        }

        $this->pTextBox($xRes, $y, $wRes, $h, $textoFisco, $aFontSec, 'T', 'L', 1, '', false);

        // QUADRO QR CODE (NT 2026.010 - Grupo ZX)
        if ($wQRCode > 0) {
            $xQR = $x + $wAdicionais;
            $this->pTextBox($xQR, $y, $wQRCode, $h, "QR CODE", $aFontSec, 'T', 'C', 1, '');
            $this->pRenderQRCode($this->qrCodeDanfe, $xQR + 3.5, $y + 4, 25, 25);
        }

        return $y + $h;
    }

    /**
     * Renderiza a imagem do QR Code no PDF
     *
     * @param string $conteudo
     * @param float  $x
     * @param float  $y
     * @param float  $w
     * @param float  $h
     * @return void
     */
    protected function pRenderQRCode($conteudo, $x, $y, $w, $h)
    {
        if (empty($conteudo)) {
            return;
        }

        $folder = __DIR__ . '/../Legacy/QRCode/tmp/';
        if (!is_dir($folder)) {
            @mkdir($folder, 0777, true);
        }

        $qrFile = $folder . 'qr_' . md5($conteudo . microtime()) . '.png';
        $qrLib = __DIR__ . '/../Legacy/QRCode/qrlib.php';

        if (file_exists($qrLib)) {
            require_once $qrLib;
            if (class_exists('QRCode')) {
                \QRCode::png(htmlspecialchars_decode($conteudo), $qrFile, 'M', 4, 1);
                if (file_exists($qrFile)) {
                    $this->pdf->Image($qrFile, $x, $y, $w, $h, 'png');
                    @unlink($qrFile);
                }
            }
        }
    }

    /**
     * Montagem geral do DANFE com ajustes de altura para a Reforma Tributária
     *
     * @param string  $orientacao
     * @param string  $papel
     * @param string  $logoAlign
     * @param integer $situacaoExterna
     * @param boolean $classPdf
     * @param string  $depecNumReg
     * @param float   $margSup
     * @param float   $margEsq
     * @param float   $margInf
     * @return mixed
     */
    public function montaDANFE(
        $orientacao = '',
        $papel = 'A4',
        $logoAlign = 'C',
        $situacaoExterna = self::SIT_NONE,
        $classPdf = false,
        $depecNumReg = '',
        $margSup = 2,
        $margEsq = 2,
        $margInf = 2
    ) {
        if ($orientacao == '') {
            $orientacao = $this->orientacao;
            if ($orientacao == '') {
                $orientacao = ($this->tpImp == '1') ? 'P' : 'L';
            }
        }

        $this->orientacao = $orientacao;
        $this->pAdicionaLogoPeloCnpj();
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        $this->situacao_externa = $situacaoExterna;
        $this->numero_registro_dpec = $depecNumReg;

        if ($classPdf) {
            $this->pdf = $classPdf;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }

        $xInic = $margEsq;
        $yInic = $margSup;

        if ($this->orientacao == 'P') {
            $maxW = 210;
            $maxH = 297;
        } else {
            $maxH = 210;
            $maxW = 297;
            $this->wCanhoto *= $this->qCanhoto;
        }

        $totPag = 1;
        $this->wPrint = $maxW - ($margEsq * 2);
        $this->hPrint = $maxH - $margSup - $margInf;

        $this->pdf->aliasNbPages();
        $this->pdf->setMargins($margEsq, $margSup);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->open();
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->setTextColor(0, 0, 0);

        // Duplicatas
        $linhasDup = 0;
        if (($this->dup->length > 0) && ($this->dup->length <= 7)) {
            $linhasDup = 1;
        } elseif (($this->dup->length > 7) && ($this->dup->length <= 14)) {
            $linhasDup = 2;
        } elseif ($this->dup->length > 14) {
            $linhasDup = 3;
        }

        // ISSQN (NT 2026.010 item 4.4)
        $linhaISSQN = 0;
        $hasIM = !empty($this->emit->getElementsByTagName("IM")->item(0)->nodeValue);
        $hasServ = (isset($this->ISSQNtot)) && ($this->pSimpleGetValue($this->ISSQNtot, 'vServ') > 0);
        if ($hasIM || $hasServ) {
            $linhaISSQN = 1;
        }

        // Dados adicionais
        if ($this->orientacao == 'P') {
            $this->wAdic = round($this->wPrint * 0.65, 0);
        } else {
            $this->wAdic = round(($this->wPrint - $this->wCanhoto) * 0.5, 0);
        }

        $fontProduto = array('font' => $this->fontePadrao, 'size' => 6.5, 'style' => '');
        $this->textoAdic = $this->pGeraInformacoesDasNotasReferenciadas();

        if (isset($this->infAdic)) {
            if ($this->textoAdic != '') {
                $this->textoAdic .= ". \r\n";
            }
            $infCpl = !empty($this->infAdic->getElementsByTagName("infCpl")->item(0)->nodeValue)
                ? trim($this->pAnfavea($this->infAdic->getElementsByTagName("infCpl")->item(0)->nodeValue))
                : '';
            $this->textoAdic .= 'Inf. Contribuinte: ' . $infCpl;
        }

        if ($this->exibirValorTributos) {
            $flagVTT = strpos(strtolower(trim($this->textoAdic)), 'valor');
            $flagVTT = $flagVTT || strpos(strtolower(trim($this->textoAdic)), 'vl');
            $flagVTT = $flagVTT && strpos(strtolower(trim($this->textoAdic)), 'aprox');
            $flagVTT = $flagVTT && (strpos(strtolower(trim($this->textoAdic)), 'trib') || strpos(strtolower(trim($this->textoAdic)), 'imp'));
            $vTotTrib = $this->ICMSTot ? $this->pSimpleGetValue($this->ICMSTot, 'vTotTrib') : '';
            if ($vTotTrib != '' && !$flagVTT) {
                $this->textoAdic .= "\n Valor Aproximado dos Tributos : R$ " . number_format((float)$vTotTrib, 2, ",", ".");
            }
        }

        $this->textoAdic = str_replace(";", "\n", $this->textoAdic);
        $alinhas = explode("\n", $this->textoAdic);
        $numlinhasdados = 0;
        foreach ($alinhas as $linha) {
            $numlinhasdados += $this->pGetNumLines($linha, $this->wAdic, $fontProduto);
        }

        $hdadosadic = max(round(($numlinhasdados + 3) * $this->pdf->FontSize, 0), 22);

        // Alturas calculadas para acomodar o cabeçalho ampliado (+7mm CRT) e totais de IBS/CBS/IS (+18mm)
        $hcabecalho = 54;
        if ($this->retirada) {
            $hcabecalho += 25;
        }
        if ($this->entrega) {
            $hcabecalho += 25;
        }

        $hdestinatario = 25;
        $hduplicatas = 12;
        $himposto = 35; // Total Produtos + Total ICMS/IPI + Total IBS/CBS/IS
        $htransporte = 25;
        $hissqn = 11;
        $hfooter = $hdadosadic;
        $hCabecItens = 6;

        $is_new_page = false;
        if ($hdadosadic > 40) {
            $hfooter = 4;
            $is_new_page = true;
            $hDispo1 = $this->hPrint - 10 - ($hcabecalho + $hdestinatario + ($linhasDup * $hduplicatas)
                + $himposto + $htransporte + ($linhaISSQN * $hissqn) + $hfooter + $hCabecItens + $this->pSizeExtraTextoFatura());
        } else {
            $hDispo1 = $this->hPrint + 5 - ($hcabecalho + $hdestinatario + ($linhasDup * $hduplicatas)
                + $himposto + $htransporte + ($linhaISSQN * $hissqn) + $hdadosadic + $hCabecItens + $this->pSizeExtraTextoFatura());
        }

        if ($this->orientacao == 'P') {
            $hDispo1 -= 23 * $this->qCanhoto;
            $w = $this->wPrint;
        } else {
            $w = $this->wPrint - $this->wCanhoto;
        }

        $hDispo2 = $this->hPrint - 10 - $hcabecalho - 4;

        // Estimar paginação baseado na quantidade de itens (mínimo 10.5mm por item na NT 2026.010)
        $hItemEstimado = 11.0;
        $totItens = $this->det->length;
        $itensPag1 = max(floor($hDispo1 / $hItemEstimado), 1);
        $itensPagSeg = max(floor($hDispo2 / $hItemEstimado), 1);

        if ($totItens > $itensPag1) {
            $totPag = 1 + ceil(($totItens - $itensPag1) / $itensPagSeg);
        } else {
            $totPag = 1;
        }

        // #####################################################################
        // MONTAGEM PÁGINA 1
        // #####################################################################
        $pag = 1;
        $x = $xInic;
        $y = $yInic;

        if ($this->orientacao == 'P') {
            for ($k = 1; $k <= $this->qCanhoto; $k++) {
                $y = $this->pCanhoto($x, $y);
            }
        } else {
            for ($k = 1; $k <= $this->qCanhoto; $k++) {
                $this->pCanhoto($x, $y);
                $x = 25 * $k;
            }
        }

        $y = $this->pCabecalhoDANFE($x, $y, $pag, $totPag);
        $y = $this->pDestinatarioDANFE($x, $y + 1);

        if ($this->retirada) {
            $y = $this->pRetiradaDANFE($x, $y + 1);
        }
        if ($this->entrega) {
            $y = $this->pEntregaDANFE($x, $y + 1);
        }

        $y = $this->pFaturaDANFE($x, $y + 1);
        $y = $this->pImpostoDANFE($x, $y + 1);

        // CÁLCULO DO ISSQN: posicionado imediatamente após Total IBS/CBS/IS (NT 2026.010)
        if ($linhaISSQN == 1) {
            $y = $this->pIssqnDANFE($x, $y + 1);
        }

        $y = $this->pTransporteDANFE($x, $y + 1);

        $nInicial = 0;
        $y = $this->pItensDANFE($x, $y + 1, $nInicial, $hDispo1, $pag, $totPag, $hCabecItens);

        if (!$is_new_page) {
            $y = $this->pDadosAdicionaisDANFE($x, $y, $hdadosadic);
        }

        if ($this->orientacao == 'P') {
            $this->pRodape($xInic, $y - 1);
        } else {
            $this->pRodape($xInic, $this->hPrint + 1);
        }

        // #####################################################################
        // PÁGINAS SEGUINTES
        // #####################################################################
        for ($n = 2; $n <= $totPag; $n++) {
            $this->pdf->setMargins($margEsq, $margSup);
            $this->pdf->addPage($this->orientacao, $this->papel);
            $this->pdf->setLineWidth(0.1);
            $this->pdf->setTextColor(0, 0, 0);

            $x = $xInic;
            $y = $yInic;

            $y = $this->pCabecalhoDANFE($x, $y, $n, $totPag);
            $y = $this->pItensDANFE($x, $y, $nInicial, $hDispo2, $n, $totPag, $hCabecItens);

            if ($this->orientacao == 'P') {
                $this->pRodape($xInic, $y + 4);
            } else {
                $this->pRodape($xInic, $this->hPrint + 4);
            }

            if ($n == $totPag && $this->qtdeItensProc < $totItens) {
                $totPag++;
            }
        }

        if ($is_new_page) {
            $this->pdf->setMargins($margEsq, $margSup);
            $this->pdf->addPage($this->orientacao, $this->papel);
            $this->pdf->setLineWidth(0.1);
            $this->pdf->setTextColor(0, 0, 0);

            $x = $xInic;
            $y = $yInic;
            $y = $this->pCabecalhoDANFE($x, $y, $totPag, $totPag);
            $hdadosadic = $this->hPrint - $y;
            $y = $this->pDadosAdicionaisDANFE($x, $y, $hdadosadic);
        }

        if ($classPdf !== false) {
            return array(
                'id' => str_replace('NFe', '', $this->infNFe->getAttribute("Id")),
                'classe_PDF' => $this->pdf
            );
        }

        return str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
    }
}
