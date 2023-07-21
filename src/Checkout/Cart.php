<?php

namespace Tuezy\Checkout;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

class Cart
{
    use CartCoupons, CartTools, CartValidators;

    /**
     * @var \Tuezy\Checkout\Contracts\Cart
     */
    private $cart;

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(

    )
    {
        $this->initCart();
    }

    /**
     * Returns cart.
     *
     * @return \Tuezy\Checkout\Contracts\Cart|null
     */
    public function initCart()
    {
        $this->getCart();

        if ($this->cart) {
            $this->removeInactiveItems();
        }
    }

    /**
     * Returns cart.
     *
     * @return \Tuezy\Checkout\Contracts\Cart|null
     */
    public function getCart(): ?\Tuezy\Checkout\Contracts\Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        if (auth()->guard()->check()) {
            $this->cart = $this->cartRepository->findOneWhere([
                'customer_id' => auth()->guard()->user()->id,
                'is_active'   => 1,
            ]);
        } elseif (session()->has('cart')) {
            $this->cart = $this->cartRepository->find(session()->get('cart')->id);
        }

        return $this->cart;
    }

    /**
     * Set cart model to the variable for reuse
     *
     * @param \Tuezy\Checkout\Contracts\Cart
     * @return  void
     */
    public function setCart($cart)
    {
        $this->cart = $cart;
    }

    /**
     * Reset cart
     *
     * @return  void
     */
    public function resetCart()
    {
        $this->cart = null;
    }

}
