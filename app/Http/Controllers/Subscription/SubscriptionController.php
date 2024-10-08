<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\StoreTransactionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Http\Resources\Subscription\TransactionResource;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Services\PaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class SubscriptionController extends Controller
{
    protected PaymentService $paymentService;

    /**
     * @param PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @param StoreSubscriptionRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function store(StoreSubscriptionRequest $request, User $user): JsonResponse
    {
        $credentials = $request->validated();

        return $this->jsonResponse(
            [
                'subscription' => SubscriptionResource::make(
                    $this->paymentService->storeSubscription($user, $credentials)
                )
            ],
            'Subscription created successfully.',
            201
        );
    }

    /**
     * @param UpdateSubscriptionRequest $request
     * @param User $user
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function update(UpdateSubscriptionRequest $request, User $user, Subscription $subscription): JsonResponse
    {
        $credentials = $request->validated();

        if ($user->id !== $subscription->user_id) {
            return $this->jsonResponse(null, 'Unauthorized', 403);
        }

        $this->paymentService->updateSubscription($subscription, $credentials);

        return $this->jsonResponse(null, 'Subscription updated.');
    }

    /**
     * @param User $user
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function destroy(User $user, Subscription $subscription): JsonResponse
    {
        if ($user->id !== $subscription->user_id) {
            return $this->jsonResponse(null, 'Unauthorized', 403);
        }

        $this->paymentService->destroySubscription($subscription);

        return $this->jsonResponse(null, 'Subscription deleted successfully');
    }

    /**
     * @param StoreTransactionRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function createTransaction(StoreTransactionRequest $request, User $user): JsonResponse
    {
        $credentials = $request->validated();

        if ($this->paymentService->processPayment($user, Subscription::PRICE)) {
            $transaction = $this->paymentService->createTransaction(
                $user,
                $credentials['subscription_id'],
                Subscription::PRICE
            );

            Mail::to($user->email)->send(new \App\Mail\PaymentReceived($transaction));

            return $this->jsonResponse([
                'transaction' => TransactionResource::make($transaction)
            ], 'Transaction created successfully.', 201);
        }

        return $this->jsonResponse(null, 'Payment failed', 400);
    }
}
