<?php
include 'header.php';

$directory = "files/";
$invoices = [];
$numerodeXML = 0;
$totalIVA = 0;

foreach (glob($directory . "/*.xml") as $file) {
    $numerodeXML+=1;
    $xml = simplexml_load_file($file);

    $namespaces = $xml->getNamespaces(true);
    $attachment = $xml->children($namespaces['cac'])->Attachment;
    $externalReference = $attachment->children($namespaces['cac'])->ExternalReference;
    $xmlEspecifico = $externalReference->children($namespaces['cbc'])->Description;
    //echo $xmlEspecifico->asXML();
    $xmlFactura = simplexml_load_string($xmlEspecifico);

    if ($xml !== false) {
        $namespaces = $xml->getNamespaces(true);
        $namespacesFactura = $xmlFactura->getNamespaces(true);

        foreach ($namespaces as $prefix => $namespace) {
            $xml->registerXPathNamespace($prefix, $namespace);
        }

        foreach ($namespacesFactura as $prefix => $namespace) {
            $xmlFactura->registerXPathNamespace($prefix, $namespace);
        }

        $totalIVACadena = (string)$xmlFactura->xpath('//cac:TaxTotal/cbc:TaxAmount')[0];
        $totalIVA = $totalIVA + (float)$totalIVACadena;
        if($numerodeXML===1){
            $primeraFactura = (string)$xml->xpath('//cbc:ID')[0];
        }

        $cliente = [
            'Nombre' => (string)$xmlFactura->xpath('//cac:PartyLegalEntity/cbc:RegistrationName')[1],
            'ID' => (string)$xmlFactura->xpath('//cac:PartyLegalEntity/cbc:CompanyID')[1],
            'Correo' => (string)$xmlFactura->xpath('//cac:Contact/cbc:ElectronicMail')[1]
        ];

        $invoice = [
            'Proveedor' => (string)$xmlFactura->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name')[0],
            'ID' => (string)$xmlFactura->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID')[0],
            'Correo' => (string)$xmlFactura->xpath('//cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail')[0],
            'NumeroFactura' => (string)$xml->xpath('//cbc:ID')[0],
            'FechaEmision' => (string)$xml->xpath('//cbc:IssueDate')[0],
            'ValorNeto' => (string)$xmlFactura->xpath('//cac:LegalMonetaryTotal/cbc:LineExtensionAmount')[0],
            'Impuestos' => (string)$xmlFactura->xpath('//cac:TaxTotal/cbc:TaxAmount')[0],
            'ValorTotal' => (string)$xmlFactura->xpath('//cac:LegalMonetaryTotal/cbc:PayableAmount')[0]
        ];

        $productos = '<table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Precio unitario</th>
                                        <th>Bruto</th>
                                        <th>Impuesto</th>
                                        <th>Total producto</th>
                                    </tr>
                                    </thead>
                                    <tbody>';

        $items = $xmlFactura->xpath('//cac:InvoiceLine');

        foreach ($items as $item) {
            $descripcion = (string) ($item->xpath('.//cac:Item/cbc:Description')[0] ?? 'N/A');
            $cantidad = (string) ($item->xpath('.//cbc:InvoicedQuantity')[0] ?? '0');
            $precioUnitario = (string) ($item->xpath('.//cac:Price/cbc:PriceAmount')[0] ?? '0');
            $bruto = (float)$cantidad * (float)$precioUnitario;
            $impuesto = (string) ($item->xpath('.//cac:TaxTotal/cbc:TaxAmount')[0] ?? '0');
            $totalProducto = (float)$impuesto + $bruto;

            $productos .= '<tr>';
            $productos .= '<td>' . htmlspecialchars($descripcion) . '</td>';
            $productos .= '<td>' . htmlspecialchars($cantidad) . '</td>';
            $productos .= '<td>' . htmlspecialchars($precioUnitario) . '</td>';
            $productos .= '<td>' . number_format($bruto, 2) . '</td>';
            $productos .= '<td>' . htmlspecialchars($impuesto) . '</td>';
            $productos .= '<td>' . number_format($totalProducto, 2) . '</td>';
            $productos .= '</tr>';
        }

        $productos .= '</tbody></table>';

        array_push($invoice, $productos);

        $invoices[] = $invoice;
    }
}

echo '<script type="application/javascript"> const targetValue =' . $numerodeXML .';</script>';
echo '<script type="application/javascript"> const targetValueIVA =' . $totalIVA .';</script>';
?>

    <main>
        <section id="panelGraficas">
            <div class="container-fluid" id="contenedorPanelGraficas">
                <div class="container">
                    <h1>Web XML application</h1>

                    <div class="row">
                        <div class="col-md-6 cuadrodeDato">
                            <span id="number-display">0</span>
                        </div>
                        <div class="col-md-1">

                        </div>
                        <div class="col-md-9">
                            <br><br>
                            <h2>Número de Facturas XML leídas</h2>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 cuadrodeDato">
                            <span id="number-iva">0</span>
                        </div>
                        <div class="col-md-1">

                        </div>
                        <div class="col-md-5">
                            <br><br>
                            <h2>Total IVA deducible del impuesto de renta</h2>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-4">
                            <div class="list-group" id="list-tab" role="tablist">
                                <?php

                                //Imprimimos todos los botones de facturas
                                $variador = 0;
                                $detallesFacturas = [];

                                foreach($invoices as $invoice) {
                                    $nuevaFactura = "";
                                    $nuevoDetalleFactura = "";
                                    if($variador===0){
                                        $nuevaFactura.= '<a class="list-group-item list-group-item-action show active"';
                                        $nuevoDetalleFactura .= '<div class="tab-pane fade show active" id="list-objeto'. $variador.'" role="tabpanel" aria-labelledby="list-objeto'. $variador .'-list">';
                                    }else{
                                        $nuevaFactura.= '<a class="list-group-item list-group-item-action"';
                                        $nuevoDetalleFactura .= '<div class="tab-pane fade" id="list-objeto'. $variador.'" role="tabpanel" aria-labelledby="list-objeto'. $variador .'-list">';
                                    }
                                    $nuevaFactura.= ' id="list-objeto'. $variador.'-list" data-bs-toggle="list" href="#list-objeto'. $variador . '" role="tab" aria-controls="objeto'. $variador .'">' . $invoice['NumeroFactura'] . '</a>';
                                    $nuevoDetalleFactura .= '<div class="row"><div class="col-md-8 mb-2"> <h3>' . $invoice['Proveedor'] .'</h3></div><div class="col-md-4 mb-2"><h3>NIT: ' . $invoice['ID'] .'</h3></div></div>';
                                    $nuevoDetalleFactura .= '<form class="row g-3"><div class="col-md-6 mb-2"><label for="invoiceNumber" class="form-label">Número de Factura</label>
                                        <input type="text" class="form-control" id="invoiceNumber" value="'. $invoice['NumeroFactura'] .'" disabled></div><div class="col-md-6 mb-2">
                                        <label for="issueDate" class="form-label">Fecha de Emisión</label>
                                        <input type="text" class="form-control" id="issueDate" value="'. $invoice['FechaEmision']. '" disabled>
                                    </div><hr>
                                    <div class="col-md-4 mb-2">
                                        <label for="customerName" class="form-label">Cliente</label>
                                        <input type="text" class="form-control" id="customerName" value="'. $cliente['Nombre'] .'" disabled>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="customerName" class="form-label">Cédula de Ciudadanía</label>
                                        <input type="text" class="form-control" id="customerName" value="'. $cliente['ID']. '" disabled>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="customerName" class="form-label">Correo electrónico</label>
                                        <input type="text" class="form-control" id="customerName" value="'. $cliente['Correo'] .'" disabled>
                                    </div>
                                </form><hr>';
                                    $nuevoDetalleFactura.= '<hr>';
                                    $nuevoDetalleFactura.= $invoice[0];
                                    $nuevoDetalleFactura .= '<form>
                                    <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label for="invoiceNumber" class="form-label">Valor Neto</label>
                                        <input type="text" class="form-control controlesFinales" id="invoiceNumber" value="'. $invoice['ValorNeto'] .'" disabled>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="issueDate" class="form-label">Impuestos</label>
                                        <input type="text" class="form-control controlesFinales" id="issueDate" value="'. $invoice['Impuestos'].'" disabled>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="customerName" class="form-label">Total</label>
                                        <input type="text" class="form-control controlesFinales" id="customerName" value="'. $invoice['ValorTotal'] .'" disabled>
                                    </div>
                                </div>
                                </form>';
                                    $nuevoDetalleFactura .= '</div>'; //Cierre del tab-pane
                                    echo $nuevaFactura;
                                    array_push($detallesFacturas, $nuevoDetalleFactura);
                                    $variador++;
                                }

                                ?>
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="tab-content" id="nav-tabContent">
                                <?php
                                foreach ($detallesFacturas as $detalleFactura) {
                                    echo $detalleFactura;
                                }?>
                            </div>


                        </div>
                    </div>
                </div>


            </div>
        </section>
    </main>


<?php

include 'footer.php';
?>
