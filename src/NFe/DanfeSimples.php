<?php

namespace NFePHP\DA\NFe;

use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use \Exception;
use InvalidArgumentException;


class DanfeSimples extends Common
{

    /**
     * Tamanho do Papel
     *
     * @var string
     */
    public $papel = 'A5';
    /**
     * Nome da Fonte para gerar o DANFE
     * @var string
     */
    protected $fontePadrao = 'Arial';

     /**
     * Fonte Default
     * @var string
     */
    protected $fonteSize = 6;
    /**
     * XML NFe
     *
     * @var string
     */
    protected $xml;
    /**
     * mesagens de erro
     *
     * @var string
     */
    protected $errMsg = '';
    /**
     * status de erro true um erro ocorreu false sem erros
     *
     * @var boolean
     */
    protected $errStatus = false;
    /**
     * Dom Document
     *
     * @var \NFePHP\DA\Legacy\Dom
     */
    protected $dom;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $infNFe;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $ide;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $nfeProc;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $entrega;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $retirada;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $emit;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $dest;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $enderEmit;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $enderDest;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $det;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $cobr;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $dup;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $ICMSTot;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $ISSQNtot;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $transp;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $transporta;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $veicTransp;
    /**
     * Node reboque
     *
     * @var \DOMNode
     */
    protected $reboque;
    /**
     * Node infAdic
     *
     * @var \DOMNode
     */
    protected $infAdic;
    /**
     * Tipo de emissão
     *
     * @var integer
     */
    protected $tpEmis;
    /**
     * Node infProt
     *
     * @var \DOMNode
     */
    protected $infProt;
    /**
     * 1-Retrato/ 2-Paisagem
     *
     * @var integer
     */
    protected $tpImp;
    /**
     * Node compra
     *
     * @var \DOMNode
     */
    protected $compra;

    /**
     * quantidade de canhotos a serem montados, geralmente 1 ou 2
     *
     * @var integer
     */
    public $qCanhoto = 1;

     /**
     * largura do canhoto (25mm) apenas para a formatação paisagem
     * @var float
     */
    protected $wCanhoto = 25;

    /**
     * __construct
     *
     * @name  __construct
     *
     * @param string $xml Conteúdo XML da NF-e (com ou sem a tag nfeProc)
     */
    public function __construct($xml, $orientacao = 'P', $logo = '')
    {

        $this->xml = $xml;

        $this->logomarca = $logo;

        $this->papel = array(100,100);

        if (!empty($this->xml)) {
            $this->dom = new Dom();
            $this->dom->loadXML($this->xml);

            $this->ide        = $this->dom->getElementsByTagName("ide")->item(0);
            $this->infNFe     = $this->dom->getElementsByTagName("infNFe")->item(0);
            $this->nfeProc    = $this->dom->getElementsByTagName("nfeProc")->item(0);
            $this->emit       = $this->dom->getElementsByTagName("emit")->item(0);
            $this->entrega    = $this->dom->getElementsByTagName("entrega")->item(0);
            $this->retirada   = $this->dom->getElementsByTagName("retirada")->item(0);
            $this->dest       = $this->dom->getElementsByTagName("dest")->item(0);
            $this->enderEmit  = $this->dom->getElementsByTagName("enderEmit")->item(0);
            $this->enderDest  = $this->dom->getElementsByTagName("enderDest")->item(0);
            $this->ICMSTot    = $this->dom->getElementsByTagName("ICMSTot")->item(0);
            $this->transp     = $this->dom->getElementsByTagName('transp')->item(0); 

            //valida se o XML é uma NF-e modelo 55, pois não pode ser 65 (NFC-e)
            if ($this->pSimpleGetValue($this->ide, "mod") != '55') {
                throw new InvalidArgumentException("O xml do DANFE deve ser uma NF-e modelo 55");
            }
        }

        $this->orientacao = $orientacao;
    }

    public function monta( 
        $margSup = 2,
        $margEsq = 2,
        $margInf = 2) {

        if (empty($this->orientacao)) {
            $this->orientacao = 'L';
        }

        $this->pdf = new Pdf($this->orientacao, 'mm', array(100,100));
        
        $xInic = $margEsq;
        $yInic = $margSup;
        if ($this->orientacao == 'P') {
            $maxW = 100;
            $maxH = 210;
        } else {
            $maxH = 210;
            $maxW = 100;
            //se paisagem multiplica a largura do canhoto pela quantidade de canhotos
            $this->wCanhoto *= $this->qCanhoto;
        }
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $maxW-($margEsq*2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $maxH-$margSup-$margInf;
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        // fixa as margens
        $this->pdf->setMargins($margEsq, $margSup);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(0, 0, 0);
        // inicia o documento
        $this->pdf->open();
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, array(100,100));
        $this->pdf->setLineWidth(0.1);
        $this->pdf->setTextColor(0, 0, 0);

        $x = $xInic;
        $y = $yInic;

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

        $this->pTextBox($x, $y, 96, 96, '', $aFont, 'T', 'L', 1, '');

        $this->addLogo($this->logomarca, $x, $y);

        $this->addCabecalho($x, $y);

        $this->codeChaveAcesso($x, $y);

        $this->addEmitente($x, $y);
        
        $this->addDest($x, $y);

        $this->addTotal($x, $y);
    }

    private function addTotal(&$x, &$y){

        $y = $y + 3;

        $oldX = $x;
        
        $oldY = $y;

        $maxW = $this->wPrint;

        $h = $this->fonteSize;

        $texto = 'TOTAL DA NFE: ';

        $w = $this->pdf->GetStringWidth($texto) + 1;

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');
        
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

        $texto = number_format($this->ICMSTot->getElementsByTagName('vNF')->item(0)->nodeValue, 2, ",", ".");

        $this->pTextBox($w + 3, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $vol = $this->transp->getElementsByTagName('vol');

        if ($vol->length){

            $vol = $vol->item(0);

            $x = round($maxW*0.50, 0);

            $texto = 'Qtd. Volumes: ';

            $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');

            $w = $this->pdf->GetStringWidth($texto) + 1;

            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

            $texto = '';
            
            if ($vol->getElementsByTagName('qVol')->item(0))
                $texto = $vol->getElementsByTagName('qVol')->item(0)->nodeValue;


            $x =  $x + $w;

            $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        }   
           

        
        
    }

    private function addDest(&$x, &$y){

        $y = $y + 3;

        $oldX = $x;
        
        $oldY = $y;

        $maxW = $this->wPrint;

        $h = $this->fonteSize;

        $texto = 'DESTINATÁRIO';

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');
        
        $w = round($maxW, 0);

        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y + ($this->fonteSize / 2);

        $aFont = array('font'=> $this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

        $texto = $this->dest->getElementsByTagName('xNome')->item(0)->nodeValue;
        
        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $texto = $this->makeEndereco($this->enderDest);

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $texto = $this->makeEndereco2($this->enderDest);

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $texto = 'CNPJ/CPF: ';

        if ($this->dest->getElementsByTagName('CNPJ')->length){
            
            $texto .= $this->pFormat($this->dest->getElementsByTagName('CNPJ')->item(0)->nodeValue, "##.###.###/####.##");

        } else {

            $texto .= $this->pFormat($this->dest->getElementsByTagName('CPF')->item(0)->nodeValue, "###.###.###-##");

        }

        $this->pTextBox($x, $y1, $w / 2, $h, $texto, $aFont, 'T', 'L', 0, '');

        if ($this->dest->getElementsByTagName('IE')->length){

            $texto = 'IE: ';

            $texto .= $this->dest->getElementsByTagName('IE')->item(0)->nodeValue;
            
            $this->pTextBox($w / 2, $y1, $w / 2, $h, $texto, $aFont, 'T', 'L', 0, '');

        }   

        $y1 = $y1 + ($this->fonteSize / 2) + 2;

        $y = $y1;
        
        $this->pdf->Line($x, $y, $x + $maxW , $y);

    }

    private function addEmitente(&$x, &$y){

        $y = $y + 3;

        $oldX = $x;
        
        $oldY = $y;

        $maxW = $this->wPrint;

        $h = $this->fonteSize;

        $texto = 'EMITENTE';

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');
        
        $w = round($maxW, 0);

        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y + ($this->fonteSize / 2);

        $aFont = array('font'=> $this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

        $texto = $this->emit->getElementsByTagName('xNome')->item(0)->nodeValue;
        
        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $texto = $this->makeEndereco($this->enderEmit);

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $texto = $this->makeEndereco2($this->enderEmit);

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $texto = 'CNPJ/CPF: ';

        if ($this->emit->getElementsByTagName('CNPJ')->length){
            
            $texto .= $this->pFormat($this->emit->getElementsByTagName('CNPJ')->item(0)->nodeValue, "##.###.###/####.##");

        } else {
            
            $texto .= $this->pFormat($this->emit->getElementsByTagName('CPF')->item(0)->nodeValue, "###.###.###-##");

        }

        $this->pTextBox($x, $y1, $w / 2, $h, $texto, $aFont, 'T', 'L', 0, '');

        if ($this->emit->getElementsByTagName('IE')->length){

            $texto = 'IE: ';

            $texto .= $this->emit->getElementsByTagName('IE')->item(0)->nodeValue;
            
            $this->pTextBox($w / 2, $y1, $w / 2, $h, $texto, $aFont, 'T', 'L', 0, '');

        }   

        $y1 = $y1 + ($this->fonteSize / 2) + 2;

        $y = $y1;
        
        $this->pdf->Line($x, $y, $x + $maxW , $y);

    }

    private function makeEndereco($Ender){

        $texto = '';

        $texto .= $Ender->getElementsByTagName('xLgr')->item(0)->nodeValue;

        $texto .= ', ';

        $texto .= $Ender->getElementsByTagName('nro')->item(0)->nodeValue;

        $texto .= ', ';

        $texto .= $Ender->getElementsByTagName('xBairro')->item(0)->nodeValue;

        return $texto;
    }

    private function makeEndereco2($Ender){

        $texto = '';

        $texto .= $this->pFormat($Ender->getElementsByTagName('CEP')->item(0)->nodeValue, "#####-###");

        $texto .= ' ';

        $texto .= $Ender->getElementsByTagName('xMun')->item(0)->nodeValue;

        $texto .= ' - ';

        $texto .= $Ender->getElementsByTagName('UF')->item(0)->nodeValue;

        return $texto;
    }

    private function codeChaveAcesso(&$x, &$y){

        $oldX = $x;
        
        $oldY = $y;

        $h = 32;

        $maxW = $this->wPrint;

        $logoHmm = 12;

        $w = round($maxW*0.95, 0);

        $nImgW = round($w, 0);

        $nImgH = $logoHmm;

        $xImg = ($maxW - $nImgW) / 2 + $x;

        $yImg = $y + 1;

        $chave_acesso = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));

        $this->pdf->Code128($xImg, $yImg, $chave_acesso, $nImgW, $nImgH);

        $y = $oldY + $logoHmm;

    }

    private function addCabecalho(&$x, &$y){

        $oldX = $x;
        
        $oldY = $y;

        $maxW = $this->wPrint;

        $h = $this->fonteSize;

        $texto = 'DANFE SIMPLIFICADO';

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');
        
        $w = round($maxW*0.50, 0);

        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x = $oldX;

        $y1 = $y + ($this->fonteSize / 2);

        $tpNF = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;

        if ($tpNF == 1){

            $texto = $tpNF . ' - Saída';

        } else {
            $texto = $tpNF . ' - Entrada';
        }

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');

        $texto = 'Número :';

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

        $texto = $this->ide->getElementsByTagName('nNF')->item(0)->nodeValue;

        $this->pTextBox($x + 11, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        if ($this->infNFe->getElementsByTagName('xPed')->length){

            $texto = 'Pedido : ';

            $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');

            $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

            $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

            $texto = $this->infNFe->getElementsByTagName('xPed')->item(0)->nodeValue;

            $this->pTextBox($x + 11, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

            $y1 = $y1 + ($this->fonteSize / 2);

        }

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');

        $texto = 'Emissão : ';

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');
        
        $dhEmi = ! empty($this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue) ?
                    $this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue : '';

        $texto = '';

        if (strpos($dhEmi, 'T') != -1){
            $dhEmi = explode('T', $dhEmi);
            
            $texto = $this->pYmd2dmy($dhEmi[0]);

            if (isset($dhEmi[1])){

                $texto .= ' ' . substr($dhEmi[1], 0, 5);
            }
        }


        
        $this->pTextBox($x + 11, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $oldY;

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');

        $texto = 'CHAVE DE ACESSO';

        $x = $w;

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

        $texto = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'B');

        $texto = 'PROTOCOLO DE AUTORIZAÇÃO DE USO';

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $aFont = array('font'=>$this->fontePadrao, 'size'=> $this->fonteSize, 'style'=>'');

        $y1 = $y1 + ($this->fonteSize / 2);

        if ($this->ide->getElementsByTagName("tpEmis")->item(0)->nodeValue == 1 ){
            $texto = $this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue;
        } else {
            $texto = 'DOCUMENTO SEM VALOR FISCAL';
        }

        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');

        $y1 = $y1 + ($this->fonteSize / 2);

        if ($this->infNFe->getElementsByTagName('xPed')->length){
            
            $y = $y1 + 3;

        } else {

            $y = $y1 + 1;

        }

        $x = $oldX;
    }

    private function addLogo($logomarca, &$x, &$y){

        $oldX = $x;
        
        $oldY = $y;

        $h=32;

        $maxW = $this->wPrint;

        if (is_file($this->logomarca)) {
            
            $logoInfo=getimagesize($this->logomarca);

            //largura da imagem em mm
            $logoWmm = ($logoInfo[0])*25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1])*25.4;

            $w = round($maxW*0.90, 0);

            $nImgW = round($w/2, 0);

            $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);

            $xImg = ($maxW - $nImgW) / 2;

            $yImg = $y + 1;

            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH);

            $x = $oldX;

            $y = $nImgH + 5;
        }

    }

     /**
     * printDocument
     *
     * @param  string $nome
     * @param  string $destino
     * @param  string $printer
     * @return object pdf
     */
    public function printDocument($nome = '', $destino = 'I', $printer = '')
    {
        return $this->printDANFE($nome, $destino, $printer);
    }

     /**
     * printDANFE
     * Esta função envia a DANFE em PDF criada para o dispositivo informado.
     * O destino da impressão pode ser :
     * I-browser
     * D-browser com download
     * F-salva em um arquivo local com o nome informado
     * S-retorna o documento como uma string e o nome é ignorado.
     * Para enviar o pdf diretamente para uma impressora indique o
     * nome da impressora e o destino deve ser 'S'.
     *
     * @param string $nome Path completo com o nome do arquivo pdf
     * @param string $destino Direção do envio do PDF
     * @param string $printer Identificação da impressora no sistema
     * @return string Caso o destino seja S o pdf é retornado como uma string
     * @todo Rotina de impressão direta do arquivo pdf criado
     */
    public function printDANFE($nome = '', $destino = 'I', $printer = '')
    {
        $arq = $this->pdf->Output($nome, $destino);
        if ($destino == 'S') {
            //aqui pode entrar a rotina de impressão direta
        }
        return $arq;

        /*
           Opção 1 - exemplo de script shell usando acroread
             #!/bin/sh
            if ($# == 2) then
                set printer=$2
            else
                set printer=$PRINTER
            fi
            if ($1 != "") then
                cat ${1} | acroread -toPostScript | lpr -P $printer
                echo ${1} sent to $printer ... OK!
            else
                echo PDF Print: No filename defined!
            fi
            Opção 2 -
            salvar pdf em arquivo temporario
            converter pdf para ps usando pdf2ps do linux
            imprimir ps para printer usando lp ou lpr
            remover os arquivos temporarios pdf e ps
            Opção 3 -
            salvar pdf em arquivo temporario
            imprimir para printer usando lp ou lpr com system do php
            remover os arquivos temporarios pdf
        */
    } //fim função printDANFE

}
