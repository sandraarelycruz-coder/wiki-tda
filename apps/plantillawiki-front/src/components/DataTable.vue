<template>
  <div>
    <v-data-table
      :headers="headers"
      :items="items"
      :no-data-text="noData"
      hide-default-footer
      
    >
     <template  v-slot:item.estado="{ item }">
                <v-chip :color="item.estado ? 'success' : 'error'">
                    {{ item.estado ? 'Activo' : 'Inactivo' }}
                </v-chip>
      </template>
      <template v-slot:item.acciones="{ item }" v-if="verAcciones">
        <v-row>
          <v-col cols="12" col-md="4" col-sm="3" >
            <v-btn v-tooltip:bottom="'Ver'" density="compact" icon="mdi-eye" @click="$emit('viewAction',item)"></v-btn>
             <v-btn v-tooltip:bottom="'Editar'"class="ml-2" density="compact" icon="mdi-pencil" v-if="item.estado"></v-btn>
             <v-btn v-tooltip:bottom="'Eliminar'" class="ml-2" density="compact" icon="mdi-trash-can-outline"></v-btn>
          </v-col>
        </v-row>
       </template>
  </v-data-table>
    <Pagination :total-page="totalPage" :page="page" @update:page="onPageChange"></Pagination>
  </div>
</template>
<script setup>
import Pagination from "./Pagination.vue";

const emit = defineEmits(['update:page']);

const onPageChange = (newPage) => {
  emit('update:page', newPage);
};

const props = defineProps({
  headers: {
    type: Array,
    default: [],
  },
  items: {
    type: Array,
    default: [],
  },
  noData: {
    type: String,
    default: "No hay datos",
  },
  totalPage:{
    type:Number,
    default:1
  },
  page:{
    type:Number,
    default:1
  },
  verAcciones:{
    type:Boolean,
    default:true
  }
});
</script>
