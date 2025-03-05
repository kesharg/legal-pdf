<?php

namespace App\Listeners;

use App\Models\WebhookHistory;
use App\Services\Models\Package\PackageService;
use App\Services\Models\PurchasePackage\PurchaseService;
use App\Services\Models\User\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StripeWebhookListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(object $event): void
    {
        commonLog("Stripe Webhook Received",["event" => $event],\logService()::LOG_STRIPE);

        try {
            $incomingJson = $event->payload;
            $event_type   = $incomingJson['type'];

            if ($this->isInvoicePaid($event_type)) {
                $webhookHistory = $this->invoicePaid($incomingJson);

                $packageService  = new PackageService();
                $purchaseService = new PurchaseService();

                // User Account Activation
                $package = $packageService->findByColumns([
                    "stripe_product_id" => $webhookHistory->stripe_product_id
                ]);

                // User
                $user = (new UserService())->findByColumn(["email" ,"=", $webhookHistory->customer_email]);

                // Save Package User Data
                $packageUser = $purchaseService->savePackageUserData($user, $package);

                // Place order as Purchase Package
                $purchasePackage = $purchaseService->placePackageOrder($user, $package);

                // Package Uses
                $purchaseService->savePackageUserUsages($purchasePackage, $packageUser, $package);

                // Update User Package ID :
                $user->update([
                    "package_id" => $package->id,
                    "is_active"  => 1
                ]);

                //TODO::Email Send after payment Received
            }

        }
        catch (\Throwable $e){
            commonLog("Listener Exception : " . $e->getMessage(),["error" => errorArray($e)],\logService()::LOG_STRIPE);
        }
    }

    public function invoicePaid($incomingJson)
    {
        $event_type   = $incomingJson['type'];

        $resource_id    = null;
        $resource_type  = null;
        $summary        = null;
        $resource_state = null;

        $resource_id    = $incomingJson['data']['object']['subscription'];
        $resource_type  = $incomingJson['data']['object']['lines']['data'][0]['type'];
        $summary        = $incomingJson['data']['object']['lines']['data'][0]['description'];
        $resource_state = $incomingJson['data']['object']['status'];

        // Extract customer information
        $customer_id = $incomingJson['data']['object']['customer'];
        $customer_name = $incomingJson['data']['object']['customer_name'];
        $customer_email = $incomingJson['data']['object']['customer_email'];

        $product_id = null;
        $pricing_id = null;
        // Extract product ID and pricing ID
        $line_items = $incomingJson['data']['object']['lines']['data'];
        foreach ($line_items as $item) {
            $product_id = $item['price']['product'];
            $pricing_id = $item['price']['id'];
            // You can handle multiple line items if needed
            break; // Assuming there's only one line item for simplicity
        }

        // Save incoming data
        $savingPayloads = [
            "gateway"           => "stripe",
            "webhook_id"        =>$incomingJson['id'],
            "stripe_product_id" => $product_id,
            "stripe_plan"       => $pricing_id,
            "customer_id"       => $customer_id,
            "customer_name"     => $customer_name,
            "customer_email"    => $customer_email,
            "create_time"       =>$incomingJson['created'],
            "resource_type"     =>$resource_type,
            "event_type"        => $event_type,
            "summary"           => $summary,
            "resource_id"       => $resource_id,
            "resource_state"    => $resource_state,
        ];

        $savingPayloads["parent_payment"] = $incomingJson['data']['object']['payment_intent'];
        $savingPayloads["amount_total"] = $incomingJson['data']['object']['lines']['data'][0]['amount'];
        $savingPayloads["amount_currency"] = $incomingJson['data']['object']['lines']['data'][0]['currency'];

        $savingPayloads["hook_payloads"] = json_encode($incomingJson);


        commonLog("Hook Saved",[],\logService()::LOG_STRIPE);

        return WebhookHistory::query()->create($savingPayloads);
        // Account Activations
    }


    public function isInvoicePaid(string $event_type = 'invoice.paid')
    {
        return $event_type === 'invoice.paid';
    }

    public function isSubscriptionDeletedByCustomer(string $event_type = 'customer.subscription.deleted')
    {
        return $event_type === 'customer.subscription.deleted';
    }
}
