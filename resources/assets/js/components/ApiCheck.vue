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
                <el-table :row-key="getRowKeys" :data="api_list" style="width: 100%">
                    <el-table-column prop="name" label="名称" style="width: 25%"></el-table-column>
                    <el-table-column prop="route" label="路由" style="width: 50%"></el-table-column>
                    <el-table-column prop="method" label="方法" style="width: 10%"></el-table-column>
                    <el-table-column prop="" label="操作" style="width: 10%">
                        <template slot-scope="scope">
                             <el-button size="small" type="primary" v-on:click="checkApi(1,scope.$index)"
                             v-if="scope.row.success == undefined" plain>check</el-button>
                             <i class="el-icon-success" v-if="scope.row.success == true"></i>
                             <i class="el-icon-loading" v-if="scope.row.success == 'checking'"></i>
                             <i class="el-icon-close" v-if="scope.row.success == false"></i>
                        </template>
                    </el-table-column>
                </el-table>

            </el-main>
            <el-footer>
                <el-row type="flex" class="row-bg" justify="center">
                    <el-col :span="6">
                        <el-button type="primary" v-on:click="checkApi(0)" plain>全部请求</el-button>
                        <el-progress type="circle" v-bind:percentage="100*(checked_num/(api_amount+1))"></el-progress>
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
            api_index:0,
            checked_num:0
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
        getRowKeys(row) {
            return row.id;
        },
        getCustomers:function(){
            this.$http.get('/api_list.json').then((response) => {
                this.api_list = response.json().data;
                this.api_amount = response.json().data.length-1;
            }).catch(function(response) {
                console.log(response)
            })
        },checkApi:function(type=0,index=0){
            var true_index = index
            if (this.api_list[true_index].success === true || this.api_list[true_index].success === false) {
                console.log(111);
                this.checkApi(0,true_index+1);
                return;
            }
            this.$set(this.api_list[true_index],'success', 'checking')
            this.$http.get('/api_check/'+true_index+'.json').then((response) => {
                this.$set(this.api_list[true_index],'success', response.json().data.success)
                if (response.json().data.success) {
                    this.checked_num++;
                }
                if (type==0) {
                    if (true_index <= this.api_amount-1) {
                        this.checkApi(0,true_index+1);
                    }
                }
            }).catch(function(response) {
                console.log(response)
            })
        }
    },
    components: {
        ElementUI
    }
}
</script>