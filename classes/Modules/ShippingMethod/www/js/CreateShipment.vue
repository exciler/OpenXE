<!--
SPDX-FileCopyrightText: 2022-2024 Andreas Palm

SPDX-License-Identifier: LicenseRef-EGPL-3.1
-->

<script setup>
import { useI18n } from 'vue-i18n';
import {computed, onBeforeUpdate, onMounted, ref} from "vue";
import PlusIcon from "primevue/icons/plus";
import TrashIcon from "primevue/icons/trash";
import Button from "primevue/button";
import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";

const {t, d, n} = useI18n();

const model = ref(null);
const messages = ref([]);
const products = ref({});
const submitting = ref(false);
const customs_shipment_types = ref([]);
const countries = ref({});
const carrier = ref(null);
const totalValue = computed(() => {
  let sum = 0;
  for (const pos of model.value.positions) {
    sum += (pos.menge * pos.zolleinzelwert) || 0;
  }
  return sum;
});

const totalWeight = computed(() => {
  let sum = 0;
  for (const pos of model.value.positions) {
    sum += (pos.menge * pos.zolleinzelgewicht) || 0;
  }
  return sum;
});

const availProducts = computed(() => {
  return Object.values(products.value).filter(productAvailable);
})

function addPosition() {
  model.value.positions.push({});
}

function deletePosition(index) {
  model.value.positions.splice(index, 1);
}

function productAvailable(product) {
  if (product === undefined)
    return false;
  if (product.WeightMin > model.value.weight || product.WeightMax < model.value.weight)
    return false;
  return true;
}

function serviceAvailable(service) {
  if (!products.value.hasOwnProperty(model.value.product))
    return false;
  return products.value[model.value.product].AvailableServices.indexOf(service) >= 0;
}
function customsRequired() {
  return countries.value[model.value.country].eu === '0';
}
function autoselectproduct() {
  if (productAvailable(products.value[model.value.product]))
    return;

  model.value.product = availProducts.value[0]?.Id;
}

function submit() {
  submitting.value = true;
  axios.post(location.href, {...model.value, submit: 'print'})
      .then(response => messages.value = response.data.messages)
      .catch(AlertErrorHandler)
      .finally(() => submitting.value = false);
}

onMounted(() => {
  const data = window.createShipmentData;
  if (data === undefined)
    return;
  products.value = data.products;
  countries.value = data.countries;
  customs_shipment_types.value = data.customs_shipment_types;
  model.value = data.form;
  carrier.value = data.carrier;
  autoselectproduct();
});

onBeforeUpdate(autoselectproduct);
</script>

<template>
  <div class="row" v-if="model">
    <div v-for="msg in messages" :class="msg.class">{{msg.text}}</div>
    <div>
      <h1>{{ t('title', { carrier: carrier }) }}</h1>
    </div>
    <div class="col-md-4">
      <h2>{{t('address.recipient')}}</h2>
      <table>
        <tr>
          <td>{{ t('address.addresstype')}}:</td>
          <td>
            <select v-model.number="model.addresstype">
              <option value="0">{{ t('address.type.house') }}</option>
              <option value="1">{{ t('address.type.parcelstation') }}</option>
              <option value="2">{{ t('address.type.shop') }}</option>
            </select>
          </td>
        </tr>
        <tr>
          <td>{{ t('address.name_line1') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.name"></td>
        </tr>
        <tr v-if="model.addresstype === 0">
          <td>{{ t('address.companyname_line2') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.name2"></td>
        </tr>
        <tr v-if="model.addresstype === 1 || model.addresstype === 2">
          <td>{{ t('address.postnumber') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.postnumber"></td>
        </tr>
        <tr v-if="model.addresstype === 0">
          <td>{{ t('address.companyname_line3') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.name3"></td>
        </tr>
        <tr v-if="model.addresstype === 0">
          <td>{{ t('address.streetAndNo') }}:</td>
          <td>
            <input type="text" size="30" v-model.trim="model.street">
            <input type="text" size="5" v-model.trim="model.streetnumber">
          </td>
        </tr>
        <tr v-if="model.addresstype === 1">
          <td>{{ t('address.parcelstationnumber') }}:</td>
          <td><input type="text" size="10" v-model.trim="model.parcelstationNumber"></td>
        </tr>
        <tr v-if="model.addresstype === 2">
          <td>{{ t('address.shopnumber') }}:</td>
          <td><input type="text" size="10" v-model.trim="model.postofficeNumber"></td>
        </tr>
        <tr v-if="model.addresstype === 0">
          <td>{{ t('address.addressline2') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.address2"></td>
        </tr>
        <tr>
          <td>{{ t('address.zip_city') }}:</td>
          <td><input type="text" size="5" v-model.trim="model.zip">
            <input type="text" size="30" v-model.trim="model.city">
          </td>
        </tr>
        <tr>
          <td>{{ t('address.state') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.state"></td>
        </tr>
        <tr>
          <td>{{ t('address.country') }}:</td>
          <td>
            <select v-model="model.country" required>
              <option v-for="(value, key) in countries" :value="key">{{value.name}}</option>
            </select>
          </td>
        </tr>
        <tr>
          <td>{{ t('address.contactPerson') }}:</td>
          <td><input type="text" size="36" v-model="model.contactperson"></td>
        </tr>
        <tr>
          <td>{{ t('address.email') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.email"></td>
        </tr>
        <tr>
          <td>{{ t('address.phone') }}:</td>
          <td><input type="text" size="36" v-model.trim="model.phone"></td>
        </tr>

      </table>
    </div>
    <div class="col-md-4" v-once>
      <h2>{{ t('address.shippingAddress') }}</h2>
      <table>
        <tr>
          <td>{{ t('address.name') }}</td>
          <td>{{model.original.name}}</td>
        </tr>
        <tr>
          <td>{{ t('address.contactPerson') }}</td>
          <td>{{model.original.ansprechpartner}}</td>
        </tr>
        <tr>
          <td>{{ t('address.division') }}</td>
          <td>{{model.original.abteilung}}</td>
        </tr>
        <tr>
          <td>{{ t('address.subdivision') }}</td>
          <td>{{model.original.unterabteilung}}</td>
        </tr>
        <tr>
          <td>{{ t('address.additionalInfo') }}</td>
          <td>{{model.original.adresszusatz}}</td>
        </tr>
        <tr>
          <td>{{ t('address.street') }}</td>
          <td>{{model.original.strasse}}</td>
        </tr>
        <tr>
          <td>{{ t('address.zip_city') }}</td>
          <td>{{model.original.plz}} {{model.original.ort}}</td>
        </tr>
        <tr>
          <td>{{ t('address.state') }}</td>
          <td>{{model.original.bundesland}}</td>
        </tr>
        <tr>
          <td>{{ t('address.country') }}</td>
          <td>{{model.original.land}}</td>
        </tr>
      </table>
    </div>
    <div class="col-md-4">
      <h2>{{t('package')}}</h2>
      <table>
        <tr>
          <td>{{ t('packageWeight') }}:</td>
          <td><input type="text" v-model.number="model.weight"></td>
        </tr>
        <tr>
          <td>{{ t('packageHeight') }}:</td>
          <td><input type="text" size="10" v-model.number="model.height"></td>
        </tr>
        <tr>
          <td>{{ t('packageWidth') }}:</td>
          <td><input type="text" size="10" v-model.number="model.width"></td>
        </tr>
        <tr>
          <td>{{ t('packageLength') }}:</td>
          <td><input type="text" size="10" v-model.number="model.length"></td>
        </tr>
        <tr>
          <td>{{ t('shippingProduct') }}:</td>
          <td>
            <select v-model="model.product" required>
              <option v-for="prod in availProducts" :value="prod.Id">{{prod.Name}}</option>
            </select><i>F&uuml;r Produktwahl Gewicht eingeben!</i>
          </td>
        </tr>
        <tr v-if="serviceAvailable('premium')">
          <td>{{ t('services.premium') }}:</td>
          <td><input type="checkbox" v-model="model.services.premium"></td>
        </tr>
      </table>
    </div>
    <div class="clearfix"></div>
    <div class="col-md-12">
      <h2>{{ t('other') }}</h2>
      <table>
        <tbody>
        <tr>
          <td>{{ t('references') }}:</td>
          <td><input type="text" size="36" v-model="model.order_number"></td>
        </tr>
        <tr>
          <td>{{ t('insuredValue') }}:</td>
          <td><input type="text" size="10" v-model="model.total_insured_value"/></td>
        </tr>
        </tbody>
        <tbody v-if="customsRequired()">
        <tr>
          <td>{{ t('document.invoiceNumber') }}:</td>
          <td><input type="text" size="36" v-model="model.invoice_number" required="required"></td>
        </tr>
        <tr>
          <td>{{ t('shipmentType') }}:</td>
          <td>
            <select v-model="model.shipment_type">
              <option v-for="(value, key) in customs_shipment_types" :value="key">{{value}}</option>
            </select>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
    <div class="col-md-12" v-if="customsRequired()">
      <table>
        <tr>
          <th>{{ t('common.description') }}</th>
          <th>{{ t('common.amount') }}</th>
          <th>{{ t('customs.hscode') }}</th>
          <th>{{ t('product.originCountry') }}</th>
          <th>{{ t('customs.itemValue') }}</th>
          <th>{{ t('customs.itemWeight') }}</th>
          <th>{{ t('common.totalValue') }}</th>
          <th>{{ t('common.totalWeight') }}</th>
          <th><a v-on:click="addPosition"><PlusIcon /></a></th>
        </tr>
        <tr v-for="(pos, index) in model.positions">
          <td><input type="text" v-model.trim="pos.bezeichnung" required></td>
          <td><input type="text" v-model.number="pos.menge" required></td>
          <td><input type="text" v-model.trim="pos.zolltarifnummer"></td>
          <td><input type="text" v-model.trim="pos.herkunftsland"></td>
          <td><input type="text" v-model.number="pos.zolleinzelwert"></td>
          <td><input type="text" v-model.number="pos.zolleinzelgewicht"></td>
          <td>{{ n(Number(pos.menge*pos.zolleinzelwert || 0), 'currency') }}</td>
          <td>{{ n(Number(pos.menge*pos.zolleinzelgewicht || 0), 'weight') }}</td>
          <td><a v-on:click="deletePosition(index)"><TrashIcon /></a></td>
        </tr>
        <tr>
          <td colspan="6"></td>
          <td>{{ n(totalValue, 'currency') }}</td>
          <td>{{ n(totalWeight, 'weight') }}</td>
        </tr>
      </table>
    </div>
    <div>
      <Button :label="t('printLabel')" :disabled="submitting" @click="submit" />
    </div>
  </div>
</template>

<i18n src="./locales.yaml" lang="yaml"></i18n>