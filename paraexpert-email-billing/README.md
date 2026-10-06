# ParaExpert – Coordonnées de facturation dans les emails WooCommerce

## Installation
1. Compressez le dossier `paraexpert-email-billing` en `.zip`.
2. WordPress > Extensions > Ajouter > Téléverser une extension > Activer.
   (Alternative : coller le contenu du PHP dans le plugin *Code Snippets*.)

## Étape obligatoire : vérifier les clés des champs
Les champs Téléphone 2, District et Quartier sont personnalisés, leur clé dépend de votre site.
1. WooCommerce > Commandes > ouvrez une commande récente.
2. Notez les clés du formulaire (plugin de champs checkout : réglages WooCommerce > Paiement/Champs, ou métabox « Champs personnalisés » de la commande).
3. Modifiez le tableau `paraexpert_email_fields_map()` dans le fichier PHP.

## Test
WooCommerce > Réglages > Emails > Nouvelle commande > « Envoyer un email de test », ou passez une commande test.
Les emails concernés : admin (nouvelle commande) et client (en cours, terminée, facture, etc.).
