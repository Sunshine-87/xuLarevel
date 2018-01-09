<template lang="html">
    <div id = 'app'>
        <el-container>
            <el-header>
                <el-row type="flex" class="row-bg" justify="center">
                    <el-col :span="6" style="align:center">
                        接口请求工具
                    </el-col>
                </el-row>
            </el-header>
            <el-main>
                <el-table
                    :data="api_list"
                    style="width: 100%"
                    :row-style="rowLight">
                    <el-table-column
                        prop="name"
                        label="名称"
                        style="width: 25%">
                    </el-table-column>
                    <el-table-column
                        prop="route"
                        label="路由"
                        style="width: 50%">
                    </el-table-column>
                    <el-table-column
                        prop="method"
                        label="方法"
                        style="width: 10%">
                    </el-table-column>
                </el-table>

            </el-main>
            <el-footer>
                <el-row type="flex" class="row-bg" justify="center">
                    <el-col :span="6">
                        <el-button type="primary" v-on:click="checkApi" plain>全部请求</el-button>
                        <el-progress type="circle" :percentage="0"></el-progress>
                    </el-col>
                </el-row>
            </el-footer>
        </el-container>
    </div>
</template>

<style>
  .el-table .warning-row {
    background: oldlace;
  }

  .el-table .success-row {
    background: #f0f9eb;
  }
</style>


<script>
import ElementUI from 'element-ui'

export default {
    data () {
        return {
            api_list:[],
            api_amount:0,
            api_index:0
        }
    },
    mounted () {
        this.getCustomers()
    },
    methods:{
        add:function(num){
            if(num!=''){this.a+=num}
            else{this.a++}
        },
        rowLight:function({row, rowIndex}){
            if (row.success == true) {
                return {"background": "#f0f9eb"} ;
            }
            return '';
        },
        getCustomers:function(){
            this.$http.get('/api_list.json').then((response) => {
                this.api_list = response.json().data;
                this.api_amount = response.json().data.length-1;
                console.log(response.json().data.length)
            }).catch(function(response) {
                console.log(321)
            })
        },checkApi:function(){
            this.$http.get('/api_check/'+this.api_index+'.json').then((response) => {
                this.api_list[this.api_index].success = response.json().data.success;
                console.log(this.api_list)
                this.api_index++;
                if (this.api_index <= this.api_amount) {
                    this.checkApi();
                }
            }).catch(function(response) {
                console.log(321)
            })
        }
    },
    components: {
        ElementUI
    }
}
</script>