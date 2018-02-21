Commerce Product Set
====================

This module extends Drupal Commerce by allowing the store owner to define
product sets consisting of already existing product variations. A product set is
a purchasable entity on its own and has got its own SKU as well as its own
price. The referenced variations are stored rather for informational purposes.

Adding a product set to the cart results in one single order item referencing
the product set. The order item stores information about the single parts
(variations) in an own custom field, persisting the data at the time of placing
the order. 

## Comparison to Commerce Product Bundle

Yes indeed, this module offers a very similar functionality as
[Commerce Product Bundle](https://www.drupal.org/project/commerce_product_bundle)
already does. Unfortunately, commerce_product_bundle is still not in a mature
state, especially there is an ongoing discussion about the data model,
especially regarding if and how to persist the information about the involved
products and variations at the time of placing the order.

Being under time pressure for enabling product sets (or bundles - however you
would like to call them) in a customer project forced me towards the inevitable
decision to rather create a custom module for that, rather than being able to
help improving commerce_product_bundle instead.

That's also the reason, why I'm reluctant whether to publish this module on
drupal.org, or if I should rather offer the maintainer of CPB to have a look at
this module in order to adopt the one or another thing from this module.

Besides the differences in the stored order data, I took a simpler approach to
the product set/bundle data model itself. I have went without having a dedicated
entity type for the single parts a product set consists of. A product set is a
purchasable entity on its own and directly references existing product
variations (using the extended reference field from entity_reference_quantity
module).

In the meantime, the module will be hosted on Github exclusively:
[Githup repo](https://github.com/agoradesign/commerce_product_set)
