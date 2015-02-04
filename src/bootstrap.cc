#include <math.h>
#include <stdint.h>
#include <stdio.h>
#include <algorithm>
#include <vector>
using namespace std;

typedef int64_t int64;

unsigned long RandomInteger(){ 
  static unsigned long x = 123456789;
  static unsigned long y = 362436069;
  static unsigned long z = 521288629;
  static unsigned long w = 88675123;
  unsigned long t = x ^ (x << 11);
  x = y;
  y = z;
  z = w;
  return w = (w ^ (w >> 19)) ^ (t ^ (t >> 8)); 
} 

constexpr int64 kCounter = 100000;
constexpr int64 kBucketScale = 1000;
constexpr double kBucketRange = 2.0;
constexpr int64 kQueueSize = 200;

inline void IncrementBucket(double growth_rate, vector<int64>* buckets) {
  int64 index =
      round((growth_rate + kBucketRange) / kBucketRange * kBucketScale);
  (*buckets)[max(min(index, kBucketScale * 2), 0LL)]++;
}

void Calculate(const vector<double> growth_rates) {
  vector<int64> buckets(kBucketScale * 2 + 1, 0);
  vector<double> growth_queue(kQueueSize, 0);
  double growth_rate = 0.0;
  int64 count = 0;
  for (int trial = 0; trial < kCounter; trial++) {
    for (int i = 0; i < kQueueSize; i++) {
      growth_rate -= growth_queue[i];
      growth_queue[i] = growth_rates[RandomInteger() % growth_rates.size()];
      growth_rate += growth_queue[i];
      if (trial != 0) {
        IncrementBucket(growth_rate, &buckets);
        count++;
      }
    }
  }
  int index = 0;
  double percentage = 0.0;
  for (int shift = 10; shift > 0; shift--) {
    double threshold = pow(0.1, shift / 5.0);
    while (percentage < threshold) {
      percentage += static_cast<double>(buckets[index]) / count;
      index++;
    }
    printf("%.3f",
           static_cast<double>(index - kBucketScale) /
               kBucketScale * kBucketRange);
    if (shift != 1) {
      printf("\t");
    }
  }
  puts("");
}

int main() {
  vector<double> growth_rates;
  for (double old_price = -1.0, price = -1.0;
       scanf("%lf", &price) > 0; old_price = price) {
    if (old_price < 0.0) continue;
    growth_rates.push_back(log(price) - log(old_price));
  }
  Calculate(growth_rates);
}
