<?php
$payModalUser = [];
if (isset($checkoutUser) && is_array($checkoutUser)) {
    $payModalUser = $checkoutUser;
} elseif (isset($user) && is_array($user)) {
    $payModalUser = $user;
}

$payModalName = trim((string)($payModalUser['firstname'] ?? '') . ' ' . (string)($payModalUser['lastname'] ?? ''));
$payModalAddress = trim((string)($payModalUser['address'] ?? ''));
?>
<!-- Button trigger modal -->
<!-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
    Launch static backdrop modal
</button> -->

<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Bank Transfer</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="invoice-col right">
                        <strong>Pay To</strong>
                        <address class="small-text">
                            Bolakaz Enterprise<br />
                            <br />
                            Account Name: Bolaji Motunrayo Bilikisu<br />
                            Account No/Bank: 0025463335 (GTBank)<br />
                            <!--Account No/Bank: 2126267639 (UBA)-->
                        </address>
                    </div>
                    <div class="invoice-col">
                        <strong>Invoiced To</strong>
                        <address class="small-text">
                            <?php echo e($payModalName !== '' ? $payModalName : 'Customer details will appear here after checkout details are filled.'); ?><br />
                            <?php if ($payModalAddress !== '') { ?>
                                <?php echo e($payModalAddress); ?> <br />
                            <?php } ?>

                        </address>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submitFormButton">Confirm Bank Transfer</button>
                <!-- <a href="bank_transfer.php?status=sent" class="btn btn-primary">I have sent it</a> -->
            </div>
        </div>
    </div>
</div>
