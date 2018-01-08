<template>
    <div id = 'test'>
        {{ a }}
    </div>
</template>

<script>
export default {
    data () {
      return {
        api_list:[]
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
        getCustomers:function(){
            this.$http.get('/api_list.json').then((response) => {        
                this.a = response.json().status;
                
                console.log(response.json().status)
            }).catch(function(response) {                   
                console.log(321)               
            })
        }
    }
}
</script>