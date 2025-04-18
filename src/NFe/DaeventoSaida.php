<?php

namespace NFePHP\DA\NFe;

/**
 * Esta classe gera a representação em PDF de um evento de NFe
 * NOTA: Esse documento NÃO É NORMALIZADO, nem é requerido pela SEFAZ
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      Daevento.php
 * @copyright 2009-2016 NFePHP
 * @license   http://www.gnu.org/licenses/lgpl.html GNU/LGPL v.3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 */

use Exception;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;

class DaeventoSaida extends Common
{
    public $chNFe;
    
    protected $logoAlign = 'C';
    protected $yDados = 0;
    protected $debugMode = 0;
    protected $aEnd = array();
    protected $pdf;
    protected $xml;
    protected $logomarca = '';
    protected $errMsg = '';
    protected $errStatus = false;
    protected $orientacao = 'P';
    protected $papel = 'A4';
    protected $destino = 'I';
    protected $pdfDir = '';
    protected $fontePadrao = 'Times';
    protected $version = '0.1.4';
    protected $wPrint;
    protected $hPrint;
    protected $wCanhoto;
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    protected $id;
    protected $tpAmb;
    protected $cOrgao;
    protected $xCorrecao;
    protected $xCondUso;
    protected $dhEvento;
    protected $cStat;
    protected $xMotivo;
    protected $xJust;
    protected $CNPJDest = '';
    protected $CPFDest = '';
    protected $dhRegEvento;
    protected $nProt;
    protected $tpEvento;
    protected $dhsaida = '';

    private $dom;
    private $procEventoNFe;
    private $evento;
    private $infEvento;
    private $retEvento;
    private $rinfEvento;

    /**
     * __construct
     *
     * @param string $docXML      Arquivo XML (diretório ou string)
     * @param string $sOrientacao (Opcional) Orientação da impressão P-retrato L-Paisagem
     * @param string $sPapel      Tamanho do papel (Ex. A4)
     * @param string $sPathLogo   Caminho para o arquivo do logo
     * @param string $sDestino    Destino do PDF I-browser D-download S-string F-salva
     * @param string $sDirPDF     Caminho para o diretorio de armazenamento dos arquivos PDF
     * @param string $fonteDANFE  Nome da fonte alternativa
     * @param array  $aEnd        array com o endereço do emitente
     * @param number $mododebug   0-Não 1-Sim e 2-nada (2 default)
     */
    public function __construct(
		$docXML,
        $dhsaida = '',
		$CNPJDest = '',
        $tpEvento = '',
        $sOrientacao = '',
        $sPapel = '',
        $sPathLogo = '',
        $sDestino = 'I',
        $aEnd = '',
        $sDirPDF = '',
        $fontePDF = '',
        $mododebug = 0
    ) {
        if (is_numeric($mododebug)) {
            $this->debugMode = (int) $mododebug;
        }
        if ($this->debugMode === 1) {
            // ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } elseif ($this->debugMode === 0) {
            // desativar modo debug
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        if (is_array($aEnd)) {
            $this->aEnd = $aEnd;
        }
        $this->orientacao = $sOrientacao;
        $this->papel = $sPapel;
        $this->pdf = '';
        $this->logomarca = $sPathLogo;
        $this->destino = $sDestino;
        $this->pdfDir = $sDirPDF;
        // verifica se foi passa a fonte a ser usada
        if (empty($fontePDF)) {
            $this->fontePadrao = 'Times';
        } else {
            $this->fontePadrao = $fontePDF;
        }
        // se for passado o xml
        
        $this->dom = new Dom();
        $this->dom->loadXML($docXML);
        // $this->procEventoNFe = $this->dom->getElementsByTagName("procEventoNFe")->item(0);
        // $this->evento = $this->dom->getElementsByTagName("evento")->item(0);
        // $this->infEvento = $this->evento->getElementsByTagName("infEvento")->item(0);
        // $this->retEvento = $this->dom->getElementsByTagName("retEvento")->item(0);
        $this->retRegSaida = $this->dom->getElementsByTagName("retRegSaida")->item(0);
        $this->tpEvento = $tpEvento;
        if (!in_array($this->tpEvento, ['110940'])) {
            $this->errMsg = 'Evento não implementado ' . $tpEvento . ' !!';
            $this->errStatus = true;
            return false;
        }
        $this->id = '';
        $this->chNFe = $this->retRegSaida->getElementsByTagName("chNFe")->item(0)->nodeValue;
        // $this->aEnd['CNPJ'] = $this->infEvento->getElementsByTagName("CNPJ")->item(0)->nodeValue;
        $this->tpAmb = $this->retRegSaida->getElementsByTagName("tpAmb")->item(0)->nodeValue;
		$this->dhsaida = $dhsaida;
        // $this->cOrgao = $this->retRegSaida->getElementsByTagName("cOrgao")->item(0)->nodeValue;
        // $this->xCorrecao = $this->infEvento->getElementsByTagName("xCorrecao")->item(0);
        // $this->xCorrecao = (empty($this->xCorrecao) ? '' : $this->xCorrecao->nodeValue);
        // $this->xCondUso = $this->infEvento->getElementsByTagName("xCondUso")->item(0);
        // $this->xCondUso = (empty($this->xCondUso) ? '' : $this->xCondUso->nodeValue);
        // $this->xJust = $this->infEvento->getElementsByTagName("xJust")->item(0);
        // $this->xJust = (empty($this->xJust) ? '' : $this->xJust->nodeValue);
        // $this->dhEvento = $this->infEvento->getElementsByTagName("dhEvento")->item(0)->nodeValue;
        // $this->cStat = $this->rinfEvento->getElementsByTagName("cStat")->item(0)->nodeValue;
        // $this->xMotivo = $this->rinfEvento->getElementsByTagName("xMotivo")->item(0)->nodeValue;
        if (strlen($CNPJDest) > 13)
			$this->CNPJDest = $CNPJDest;
		else 
			$this->CPFDest = $CNPJDest;

        //     '';
        // $this->CPFDest = !empty($this->rinfEvento->getElementsByTagName("CPFDest")->item(0)->nodeValue) ?
        //     $this->rinfEvento->getElementsByTagName("CPFDest")->item(0)->nodeValue :
        //     '';
        $this->dhRegEvento = $this->retRegSaida->getElementsByTagName("dtHrRegSaida")->item(0)->nodeValue;
        $this->nProt = $this->retRegSaida->getElementsByTagName("nProt")->item(0)->nodeValue;
    }

    /**
     * monta
     *
     * @param  string  $orientacao
     * @param  string  $papel
     * @param  string  $logoAlign
     * @param  int     $situacao_externa
     * @param  boolean $classe_pdf
     * @return number
     */
    public function monta($orientacao = '', $papel = 'A4', $logoAlign = 'C', $classe_pdf = false,  $situacao_externa = 1)
    {
        return $this->montaDaEventoNFe($orientacao, $papel, $logoAlign);
    }

    /**
     * montaDAEventoNFe
     *
     * Esta função monta a DaEventoNFe conforme as informações fornecidas para a classe
     * durante sua construção.
     * A definição de margens e posições iniciais para a impressão são estabelecidas no
     * pelo conteúdo da funçao e podem ser modificados.
     *
     * @param  string $orientacao (Opcional) Estabelece a orientação da impressão (ex. P-retrato),
     *  se nada for fornecido será usado o padrão da NFe
     * @param  string $papel      (Opcional) Estabelece o tamanho do papel (ex. A4)
     * @return string O ID do evento extraido do arquivo XML
     */
    public function montaDaEventoNFe($orientacao = '', $papel = 'A4', $logoAlign = 'C', $classe_pdf = false)
    {
        if ($orientacao == '') {
            $orientacao = 'P';
        }
        $this->orientacao = $orientacao;
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        if ($classe_pdf !== false) {
            $this->pdf = $classe_pdf;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }
        if ($this->orientacao == 'P') {
            // margens do PDF
            $margSup = 2;
            $margEsq = 2;
            $margDir = 2;
            // posição inicial do relatorio
            $xInic = 1;
            $yInic = 1;
            if ($this->papel == 'A4') { // A4 210x297mm
                $maxW = 210;
                $maxH = 297;
            }
        } else {
            // margens do PDF
            $margSup = 3;
            $margEsq = 3;
            $margDir = 3;
            // posição inicial do relatorio
            $xInic = 5;
            $yInic = 5;
            if ($papel == 'A4') { // A4 210x297mm
                $maxH = 210;
                $maxW = 297;
            }
        }
        // largura imprimivel em mm
        $this->wPrint = $maxW - ($margEsq + $xInic);
        // comprimento imprimivel em mm
        $this->hPrint = $maxH - ($margSup + $yInic);
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        // fixa as margens
        $this->pdf->setMargins($margEsq, $margSup, $margDir);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        // inicia o documento
        $this->pdf->open();
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->setTextColor(0, 0, 0);
        // montagem da página
        $pag = 1;
        $x = $xInic;
        $y = $yInic;
        // coloca o cabeçalho
        $y = $this->pHeader($x, $y, $pag);
        // coloca os dados da CCe
        $y = $this->pBody($x, $y + 5);
        // coloca os dados da CCe
        $y = $this->pFooter($x, $y + $this->hPrint - 20);
        // retorna o ID do evento
        if ($classe_pdf !== false) {
            $aR = ['id' => $this->id, 'classe_PDF' => $this->pdf];
            return $aR;
        } else {
            return $this->id;
        }
    }

    /**
     * pHeader
     * @param  number $x
     * @param  number $y
     * @param  number $pag
     * @return number
     */
    private function pHeader($x, $y, $pag)
    {
        $oldX = $x;
        $oldY = $y;
        $maxW = $this->wPrint;
        // ######################################################################
        // coluna esquerda identificação do emitente
        $w = round($maxW * 0.41, 0); // 80;
        if ($this->orientacao == 'P') {
            $aFont = ['font' => $this->fontePadrao,'size' => 6,'style' => 'I'];
        } else {
            $aFont = ['font' => $this->fontePadrao,'size' => 8,'style' => 'B'];
        }
        $w1 = $w;
        $h = 32;
        $oldY += $h;
        $this->pTextBox($x, $y, $w, $h);
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->pTextBox($x, $y, $w, 5, $texto, $aFont, 'T', 'C', 0, '');
        if (is_file($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            // largura da imagem em mm
            $logoWmm = ($logoInfo[0] / 72) * 25.4;
            // altura da imagem em mm
            $logoHmm = ($logoInfo[1] / 72) * 25.4;
            if ($this->logoAlign == 'L') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = $x + 1;
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                // estabelecer posições do texto
                $x1 = round($xImg + $nImgW + 1, 0);
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            }
            if ($this->logoAlign == 'C') {
                $nImgH = round($h / 3, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg = round(($w - $nImgW) / 2 + $x, 0);
                $yImg = $y + 3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            if ($this->logoAlign == 'R') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = round($x + ($w - (1 + $nImgW)), 0);
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                $x1 = $x;
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            }
            $this->pdf->image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = round($h / 3 + $y, 0);
            $tw = $w;
        }
        // Nome emitente
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 12,
            'style' => 'B'
        );
        $texto = (isset($this->aEnd['razao']) ? $this->aEnd['razao'] : '');

        $this->pTextBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        // endereço
        $y1 = $y1 + 6;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => ''
        );
        $lgr = (isset($this->aEnd['logradouro']) ? $this->aEnd['logradouro'] : '');
        $nro = (isset($this->aEnd['numero']) ? $this->aEnd['numero'] : '');
        $cpl = (isset($this->aEnd['complemento']) ? $this->aEnd['complemento'] : '');
        $bairro = (isset($this->aEnd['bairro']) ? $this->aEnd['bairro'] : '');
        $CEP = (isset($this->aEnd['CEP']) ? $this->aEnd['CEP'] : '');
        $CEP = $this->pFormat($CEP, "#####-###");
        $mun = (isset($this->aEnd['municipio']) ? $this->aEnd['municipio'] : '');
        $UF = (isset($this->aEnd['UF']) ? $this->aEnd['UF'] : '');
        $fone = (isset($this->aEnd['telefone']) ? $this->aEnd['telefone'] : '');
        $email = (isset($this->aEnd['email']) ? $this->aEnd['email'] : '');
        $foneLen = strlen($fone);
        if ($foneLen > 0) {
            $fone2 = substr($fone, 0, $foneLen - 4);
            $fone1 = substr($fone, 0, $foneLen - 8);
            $fone = '(' . $fone1 . ') ' . substr($fone2, - 4) . '-' . substr($fone, - 4);
        } else {
            $fone = '';
        }
        if ($email != '') {
            $email = 'Email: ' . $email;
        }
        $texto = "";
        $tmp_txt = trim(($lgr != '' ? "$lgr, " : '') . ($nro != 0 ? $nro : "SN") . ($cpl != '' ? " - $cpl" : ''));
        $tmp_txt = ($tmp_txt == 'SN' ? '' : $tmp_txt);
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $tmp_txt = trim($bairro . ($bairro != '' && $CEP != '' ? " - " : '') . $CEP);
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $tmp_txt = $mun;
        $tmp_txt .= ($tmp_txt != '' && $UF != '' ? " - " : '') . $UF;
        $tmp_txt .= ($tmp_txt != '' && $fone != '' ? " " : '') . $fone;
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $tmp_txt = $email;
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $this->pTextBox($x1, $y1 - 2, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        // ##################################################
        $w2 = round($maxW - $w, 0);
        $x += $w;
        $this->pTextBox($x, $y, $w2, $h);
        $y1 = $y + $h;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 16,
            'style' => 'B'
        );
        if ($this->tpEvento == '110110') {
            $texto = 'Representação Gráfica de CC-e';
        } else if ($this->tpEvento == '110940') {
            $texto = 'Representação Gráfica de Registro de Saída';
        } else {
            $texto = 'Representação Gráfica de Evento';
        }
        $this->pTextBox($x, $y + 2, $w2, 8, $texto, $aFont, 'T', 'C', 0, '');
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 12,
            'style' => 'I'
        );
        if ($this->tpEvento == '110110') {
            $texto = '(Carta de Correção Eletrônica)';
        } else if ($this->tpEvento == '110940') {
            $texto = '';
        } elseif ($this->tpEvento == '110111') {
            $texto = '(Cancelamento de NFe)';
        }
        $this->pTextBox($x, $y + 7, $w2, 8, $texto, $aFont, 'T', 'C', 0, '');
        // $texto = 'ID do Evento: ' . $this->id;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 10,
            'style' => ''
        );
        // $this->pTextBox($x, $y + 15, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->pConvertTime($this->dhRegEvento);
        $texto = 'Criado em : ' . date('d/m/Y   H:i:s', $tsHora);
        $this->pTextBox($x, $y + 12, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->pConvertTime($this->dhRegEvento);
        $texto = 'Prococolo: ' . $this->nProt . '  -  Registrado na SEFAZ em: ' . date('d/m/Y   H:i:s', $tsHora);
        $this->pTextBox($x, $y + 20, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
		$texto = '';
        // ####################################################
        $x = $oldX;
        $this->pTextBox($x, $y1, $maxW, 40);
        $sY = $y1 + 40;
        if ($this->tpEvento == '110110') {
            $texto = 'De acordo com as determinações legais vigentes, vimos por meio desta '
                . 'comunicar-lhe que a Nota Fiscal, abaixo referenciada, contém irregularidades'
                . ' que estão destacadas e suas respectivas correções, solicitamos que sejam aplicadas '
                . 'essas correções ao executar seus lançamentos fiscais.';
        } elseif ($this->tpEvento == '110111') {
            $texto = 'De acordo com as determinações legais vigentes, '
                    . 'vimos por meio desta comunicar-lhe que a Nota Fiscal, '
                    . 'abaixo referenciada, está cancelada, solicitamos que sejam '
                    . 'aplicadas essas correções ao executar seus lançamentos fiscais.';
        }
        $aFont = ['font' => $this->fontePadrao,'size' => 10,'style' => ''];
        $this->pTextBox($x + 5, $y1, $maxW - 5, 20, $texto, $aFont, 'T', 'L', 0, '', false);
        // ############################################
        $x = $oldX;
        $y = $y1;
        if ($this->CNPJDest != '') {
            $texto = 'CNPJ do Destinatário: ' . $this->pFormat($this->CNPJDest, "##.###.###/####-##");
        }
        if ($this->CPFDest != '') {
            $texto = 'CPF do Destinatário: ' . $this->pFormat($this->CPFDest, "###.###.###-##");
        }
        $aFont = ['font' => $this->fontePadrao,'size' => 12,'style' => 'B'];
        $this->pTextBox($x + 2, $y + 13, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $numNF = substr($this->chNFe, 25, 9);
        $serie = substr($this->chNFe, 22, 3);
        $numNF = $this->pFormat($numNF, "###.###.###");
        $texto = "Nota Fiscal: " . $numNF . '  -   Série: ' . $serie;
        $this->pTextBox($x + 2, $y + 19, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $bW = 87;
        $bH = 15;
        $x = 55;
        $y = $y1 + 13;
        $w = $maxW;
        $this->pdf->SetFillColor(0, 0, 0);
        $this->pdf->Code128($x + (($w - $bW) / 2), $y + 2, $this->chNFe, $bW, $bH);
        $this->pdf->SetFillColor(255, 255, 255);
        $y1 = $y + 2 + $bH;
        $aFont = ['font' => $this->fontePadrao,'size' => 10,'style' => ''];
        $texto = $this->pFormat($this->chNFe, $this->formatoChave);
        $this->pTextBox($x, $y1, $w - 2, $h, $texto, $aFont, 'T', 'C', 0, '');
        $retVal = $sY + 2;
        if ($this->tpEvento == '110110') {
            $x = $oldX;
            $this->pTextBox($x, $sY, $maxW, 15);
            $texto = $this->xCondUso;
            $aFont = ['font' => $this->fontePadrao,'size' => 8,'style' => 'I'];
            $this->pTextBox($x + 2, $sY + 2, $maxW - 2, 15, $texto, $aFont, 'T', 'L', 0, '', false);
            $retVal = $sY + 2;
        }
        // indicar sem valor
        if ($this->tpAmb != 1) {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint * 2 / 3, 0);
            } else {
                $y = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = ['font' => $this->fontePadrao,'size' => 48,'style' => 'B'];
            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = ['font' => $this->fontePadrao,'size' => 30,'style' => 'B'];
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pTextBox($x, $y + 14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        }
        return $retVal;
    }

    /**
     * pBody
     *
     * @param number $x
     * @param number $y
     */
    private function pBody($x, $y)
    {

        $maxW = $this->wPrint;

		$texto = 'De acordo com as determinações legais vigentes, comunicado SRE No 13/2010, o estado de Minas Gerais permite ao contribuinte realizar o registro eletrônico de data de saída após a autorização da NFe.';

        $aFont = ['font' => $this->fontePadrao,'size' => 10,'style' => 'B'];
		
        $this->pTextBox($x, $y, $maxW, 5, $texto, $aFont, 'T', 'L', 0, '', false);

		$y += 15;

        if ($this->tpEvento == '110110') {
            $texto = 'CORREÇÕES A SEREM CONSIDERADAS';
        } else if ($this->tpEvento == '110940') {
            $texto = 'Registro de Saída';
        } else {
            $texto = 'JUSTIFICATIVA DO CANCELAMENTO';
        }

        $aFont = ['font' => $this->fontePadrao,'size' => 13,'style' => 'B'];
        $this->pTextBox($x, $y, $maxW, 5, $texto, $aFont, 'T', 'L', 0, '', false);
        $y += 8;
        $this->pTextBox($x, $y, $maxW, 190);
        if ($this->tpEvento == '110110') {
            $texto = $this->xCorrecao;
        } elseif ($this->tpEvento == '110111') {
            $texto = $this->xJust;
        } else if ($this->tpEvento == '110940') {
            $texto = $this->dhsaida;
        }

        $aFont = ['font' => $this->fontePadrao,'size' => 12,'style' => 'B'];
        $this->pTextBox($x + 2, $y + 2, $maxW - 2, 150, $texto, $aFont, 'T', 'L', 0, '', false);
    }

    /**
     * pFooter
     *
     * @param number $x
     * @param number $y
     */
    private function pFooter($x, $y)
    {
        $w = $this->wPrint;
        if ($this->tpEvento == '110110') {
            $texto = "Este documento é uma representação gráfica da CC-e "
                    . "e foi impresso apenas para sua informação e não possui validade "
                    . "fiscal.\n A CC-e deve ser recebida e mantida em arquivo eletrônico XML "
                    . "e pode ser consultada através dos Portais das SEFAZ.";
        } elseif ($this->tpEvento == '110111') {
            $texto = "Este documento é uma representação gráfica do evento de NFe e "
                    . "foi impresso apenas para sua informação e não possui validade "
                    . "fiscal.\n O Evento deve ser recebido e mantido em arquivo "
                    . "eletrônico XML e pode ser consultada através dos Portais "
                    . "das SEFAZ.";
        } elseif ($this->tpEvento == '110940') {

			$texto = 'Este documento é uma representação gráfica do Registro de Saída e foi impresso apenas para sua informação e não possue validade fiscal. O evento de registro de saída pode ser recebido e mantido em arquivo eletrônico XML e pode ser consultada através da SEFAZ MG.';
		}

		
        $aFont = ['font' => $this->fontePadrao,'size' => 10,'style' => 'I'];
        $this->pTextBox($x, $y, $w, 20, $texto, $aFont, 'T', 'C', 0, '', false);
        $y = $this->hPrint - 4;
        $texto = "Impresso em  " . date('d/m/Y   H:i:s');
        $w = $this->wPrint - 4;
        $aFont = ['font' => $this->fontePadrao,'size' => 6,'style' => 'I'];
        $this->pTextBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
       
    }

    /**
     * printDocument
     *
     * @param  string $nome
     * @param  string $destino
     * @param  string $printer
     * @return mixed
     */
    public function printDocument($nome = '', $destino = 'I', $printer = '')
    {
        return $this->printDaEventoNFe($nome, $destino, $printer);
    }

    /**
     * printDaEventoNFe
     *
     * @param  string $nome
     * @param  string $destino
     * @param  string $printer
     * @return mixed
     */
    public function printDaEventoNFe($nome = '', $destino = 'I', $printer = '')
    {
        if ($this->pdf == null) {
            $this->montaDaEventoNFe();
        }
        return $this->pdf->Output($nome, $destino);
    }
}
