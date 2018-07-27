<?php

namespace Drupal\commerce_order_pdf\Controller;

use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_order\Entity\Order;
use Dompdf\Dompdf\Autoloader;
use Drupal\commerce_price\Price;

use Dompdf\Dompdf;

/**
 * Class CommerceOrderPdfController.
 */
class CommerceOrderPdfController extends ControllerBase {

  /**
   * Print_invoice.
   *
   * @return string
   *   Return Hello string.
   */
  public function print_invoice($commerce_order_id) {
    $commerce_order_id = \Drupal::routeMatch()->getParameter('commerce_order_id');
    $order = Order::load($commerce_order_id);
    $items = $order->getItems();
    $timestamp = $order->getCompletedTime();
    $time = date('d/m/Y H:i:s', $timestamp);
    $billing_profile = $order->getBillingProfile()->get('address')->getValue()[0];
    $cbp = $billing_profile['given_name'] . " " . $billing_profile['family_name']. ", " . '<br>' . $billing_profile['organization']. ", " . '<br>' . $billing_profile['address_line1']. ", " . $billing_profile['address_line2']. ", "  . '<br>' . $billing_profile['locality']. ", " . $billing_profile['administrative_area']. ", " . $billing_profile['country_code']. "-" . $billing_profile['postal_code'];

    $total = number_format((float)($order->getTotalPrice()->getNumber()), 2) . ' ' . ($order->getTotalPrice()->getCurrencyCode()) ;

    $row = '<tr>';
    foreach ($items as $key => $item) {
      $oid = $item->get('order_id')->getValue()[0]['target_id'];
      $title = $item->get('title')->getValue()[0]['value'];
      $price = number_format((float)($item->get('unit_price')->getValue()[0]['number']), 2, '.', '')  . ' ' . $item->get('unit_price')->getValue()[0]['currency_code'];
      $qty = $item->get('quantity')->getValue()[0]['value'];
      $row .= '<td>'.$oid.'</td>'.'<td>'.$title .'</td>'. '<td>'.$price .'</td>'. '<td>'.$qty . '</td></tr>';
    }

    $config_html = \Drupal::config('commerceorderpdf.settings')->get('invoice_html')['value'];
    $config_css = \Drupal::config('commerceorderpdf.settings')->get('invoice_css')['value'];

    $invoice = $config_html . $row . $total. '</table>'. '<table><th>Total</th><td>' . $total . '</td></table>' . '<br><div><h3>Receipent</h3>'. $cbp . '<br>' . $time . '</div>' . $config_css;

    $dompdf = new Dompdf();
    $dompdf->loadHtml($invoice);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $dompdf->stream("invoice.pdf", array("Attachment"=>0));
  }

}
