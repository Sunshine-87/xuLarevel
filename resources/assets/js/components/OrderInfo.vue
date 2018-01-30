<template>
    <div>
        <el-row>
            <el-col :span="4" class="border">

            </el-col>

            <el-col :span="16">
                <el-row>
                    <el-col :span="24">
                        <div class="grid-content bg-purple-dark">
                            E20171654151521022
                        </div>
                    </el-col>
                </el-row>
                <el-row>
                    <el-col :span="8">
                        <div class="big-content bg-purple-dark">
                            <el-row style="min-height: 36px;" class="bg-purple-light">
                                E20171654151521022
                            </el-row>
                        </div>
                    </el-col>
                    <el-col :span="16">
                        <div class="grid-content bg-purple">徐博恒(1000002016)-xuyaoxue(1000002017)-chenyanxiao(1000002018)</div>
                        <div class="grid-content bg-purple-dark">xuyaoxue(1000002017)-chenyanxiao(1000002018)-abc(1000002019)</div>
                        <div class="grid-content bg-purple">chenyanxiao(1000002018)-abc(1000002019)-bcd(1000002019)</div>
                    </el-col>
                </el-row>
                <el-row>
                    <el-col :span="8">
                        <div class="big-content bg-purple-light">
                            E20171654151521022
                        </div>
                    </el-col>
                    <el-col :span="16">
                        <div class="grid-content bg-purple">bcd(1000002019)</div>
                        <div class="grid-content bg-purple-dark">推客下级：chenyanxiao(1000002018),...</div>
                        <div class="grid-content bg-purple">团队成员：chenyanxiao(1000002018),徐博恒(1000002016),...</div>
                    </el-col>
                </el-row>
            </el-col>

            <el-col :span="4" class="border">

            </el-col>

        </el-row>
    </div>
</template>
<style scoped>
    .border {
        min-height: 2px;
    }

    .clear-bottom {
        margin-bottom: 0;
    }

  .el-row {
    margin-bottom: 20px;
    &:last-child {
      margin-bottom: 0;
    }
  }
  .el-col {
    border-radius: 4px;
  }
  .bg-purple-dark {
    background: #99a9bf;
  }
  .bg-purple {
    background: #d3dce6;
  }
  .bg-purple-light {
    background: #e5e9f2;
  }
  .big-content {
    border-radius: 4px;
    min-height: 108px;
  }
  .grid-content {
    border-radius: 4px;
    min-height: 36px;
  }
  .row-bg {
    padding: 10px 0;
    background-color: #f9fafc;
  }
</style>